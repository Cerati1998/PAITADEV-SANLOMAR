<?php

namespace App\Services\Sunat;

use App\Enums\VoidedStatus;
use App\Models\Invoice;
use App\Models\Voided;
use Greenter\See;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Único punto del código base que envía una comunicación de baja y consulta
 * su estado. Concentra la semántica que faltaba: persistir el ticket antes de
 * cualquier consulta, clasificar el desenlace en aceptada / pendiente /
 * rechazada sin lista blanca de códigos, archivar el CDR siempre que llegue,
 * y marcar los comprobantes involucrados solo cuando SUNAT aceptó realmente
 * la baja (D-03, D-11).
 *
 * No lee session(): el ámbito multi-tenant se lee del propio Voided recibido
 * (branch_id, company_id), y la empresa para las credenciales SUNAT viaja
 * dentro del UtilService que el llamador ya construyó (RESEARCH Pitfall 7).
 */
class VoidedTicketFlowService
{
    public function __construct(private UtilService $util) {}

    /**
     * Envía la comunicación de baja y, si SUNAT devuelve ticket, resuelve su
     * estado inmediatamente. TICKET-01: el ticket se persiste con un save()
     * propio antes de cualquier consulta posterior.
     */
    public function sendAndResolve(Voided $voided, string $xml, string $cdrPath): VoidedStatus
    {
        $see = $this->util->getSee();

        try {
            $result = $see->sendXmlFile($xml);
        } catch (\Throwable $e) {
            // D-06: el envío falló antes de devolver ticket — no hay nada que
            // reconsultar, es rechazo.
            return $this->markRejected($voided, [
                'success' => false,
                'error' => [
                    'code' => null,
                    'message' => $e->getMessage(),
                ],
            ]);
        }

        if (! $result->isSuccess()) {
            // D-06: sin ticket.
            return $this->markRejected($voided, $this->util->getErrorResponse($result));
        }

        $ticket = $result->getTicket();

        if (empty($ticket)) {
            // D-06: SUNAT no devolvió ticket — mismo tratamiento que un envío
            // sin éxito, no hay ticket que reconsultar.
            return $this->markRejected($voided, [
                'success' => false,
                'error' => [
                    'code' => null,
                    'message' => 'SUNAT no devolvió ticket para esta comunicación de baja.',
                ],
            ]);
        }

        // TICKET-01 — el punto exacto del defecto original: se guarda el
        // ticket con un save() propio ANTES de cualquier otra llamada de red.
        $voided->sunat_ticket = $ticket;
        $voided->save();

        return $this->resolveTicket($voided, $cdrPath, $see);
    }

    /**
     * Consulta el estado de un ticket ya persistido y clasifica el desenlace.
     * Público a propósito (D-11): la Fase 2 lo reutiliza para reconsultar
     * anulaciones pendientes desde el listado sin reenviar el XML.
     */
    public function resolveTicket(Voided $voided, string $cdrPath, ?See $see = null): VoidedStatus
    {
        $ticket = $voided->sunat_ticket;
        $see ??= $this->util->getSee();

        try {
            $result = $see->getStatus($ticket);
        } catch (\Throwable $e) {
            // Pitfall 2: ExtService::getStatus() solo atrapa SoapFault; una
            // respuesta 200 mal formada lanza InvalidServiceResponseException
            // y escaparía sin este catch, dejando la fila como rechazo
            // silencioso. TICKET-03, D-05.
            return $this->markPending($voided, 'fallo_transporte', null, $e->getMessage());
        }

        $zip = $result->getCdrZip();
        $cdr = $result->getCdrResponse();

        if (empty($zip) && ! $cdr) {
            // TICKET-02, D-05: sin excepción pero sin CDR (p. ej. código 98,
            // "en proceso"). El motivo es solo informativo (D-04); la
            // clasificación pendiente no depende de él.
            $code = $result->getError()?->getCode();
            $reason = $code === '98' ? 'codigo_98' : 'fallo_transporte';

            return $this->markPending($voided, $reason, $code, $result->getError()?->getMessage());
        }

        // Pitfall 4: el CDR puede venir poblado aunque isSuccess() sea false
        // (código propio del CDR en el rango de excepción 100-1999). Se
        // archiva siempre que llegue, sin mirar el indicador de éxito.
        if ($zip) {
            $voided->cdr_path = $cdrPath;
            Storage::put($cdrPath, $zip);
        }

        if (! $cdr) {
            // Defensivo: ExtService::processResponse() siempre rellena
            // cdrZip y cdrResponse juntos, así que este caso no debería
            // darse en la práctica; si ocurriera, no hay código de CDR con
            // el que decidir el desenlace.
            return $this->markPending(
                $voided,
                'fallo_transporte',
                $result->getError()?->getCode(),
                $result->getError()?->getMessage()
            );
        }

        // D-07, TICKET-04, WEB-05: la aceptación se decide por el código del
        // propio CDR, no por el indicador de éxito del resultado.
        if ((string) $cdr->getCode() === '0') {
            try {
                DB::transaction(function () use ($voided, $cdr) {
                    $voided->sunatResponse = $this->util->readCdr($cdr);
                    $voided->sunat_status = VoidedStatus::Aceptada;
                    $voided->sunat_ticket = null;
                    $voided->save();

                    $this->markVoidedInvoices($voided);
                });
            } catch (\Throwable $e) {
                // La transacción revierte la fila en BD pero no los atributos
                // en memoria del modelo: sin resincronizarlos aquí,
                // classifyFailure() vería el sunat_ticket en null (asignado
                // dentro del bloque revertido) y no podría distinguir este
                // caso del rechazo sin ticket (D-06), degradando una
                // aceptación de SUNAT a rechazo.
                $voided->refresh();

                return $this->markPending($voided, 'fallo_transporte', null, $e->getMessage());
            }

            return VoidedStatus::Aceptada;
        }

        // CDR presente con código distinto de 0: SUNAT ya recibió el XML, así
        // que el ticket se conserva (D-09) — limpiarlo aquí reabriría la vía
        // del error 2109 ("la comunicación de baja ya fue informada").
        return $this->markRejected($voided, $this->util->readCdr($cdr));
    }

    /**
     * Clasifica una excepción no atrapada por sendAndResolve()/resolveTicket()
     * que llega hasta el catch exterior de los llamadores Livewire (Fases 2 y
     * 3). La clasificación no depende del contenido del error (RESEARCH
     * Pitfall 3), sino de si ya existe un ticket persistido en ese punto.
     */
    public function classifyFailure(Voided $voided, \Throwable $e): VoidedStatus
    {
        // Invariante (Gap 1): un desenlace ya confirmado por un CDR de SUNAT
        // no se reescribe nunca, sin importar qué falle después. Son dos
        // situaciones, y el orden importa porque la segunda no es
        // distinguible por el ticket:
        //   1. Aceptada: la rama de aceptación ya corrió y archivó el CDR.
        //   2. Rechazada con ticket: rechazo por CDR con código != 0 (única
        //      situación en que markRejected() se invoca con ticket ya
        //      persistido). Degradarla a pendiente perdería un desenlace que
        //      SUNAT ya confirmó.
        if ($voided->sunat_status === VoidedStatus::Aceptada
            || ($voided->sunat_status === VoidedStatus::Rechazada && ! is_null($voided->sunat_ticket))) {
            Log::warning('Fallo posterior a un desenlace SUNAT ya confirmado; no se reescribe el estado', [
                'voided_id' => $voided->id,
                'sunat_status' => $voided->sunat_status?->value,
                'message' => $e->getMessage(),
            ]);

            return $voided->sunat_status;
        }

        if (is_null($voided->sunat_ticket)) {
            // D-06: el fallo ocurrió antes de que SUNAT devolviera ticket.
            return $this->markRejected($voided, [
                'success' => false,
                'error' => [
                    'code' => null,
                    'message' => $e->getMessage(),
                ],
            ]);
        }

        // El ticket ya se guardó (p. ej. un fallo de Storage::put al archivar
        // el ZIP del CDR): SUNAT ya recibió el XML, así que escribir rechazo
        // aquí reintroduciría el defecto que esta fase elimina.
        return $this->markPending($voided, 'fallo_transporte', null, $e->getMessage());
    }

    /**
     * Único punto de escritura del desenlace pendiente. No toca sunat_ticket
     * (queda guardado para la reconsulta) y nunca escribe un sunatResponse
     * null (D-04): null sería indistinguible de "nunca se envió".
     */
    private function markPending(Voided $voided, string $reason, ?string $code, ?string $message): VoidedStatus
    {
        $voided->sunat_status = VoidedStatus::Pendiente;
        $voided->sunatResponse = [
            'success' => false,
            'pending' => [
                'reason' => $reason,
                'code' => $code,
                'message' => $message,
            ],
        ];
        $voided->save();

        Log::warning('Comunicación de baja pendiente de confirmación SUNAT', [
            'voided_id' => $voided->id,
            'sunat_ticket' => $voided->sunat_ticket,
            'reason' => $reason,
        ]);

        return VoidedStatus::Pendiente;
    }

    /**
     * Único punto de escritura del desenlace rechazado. No asigna ni limpia
     * sunat_ticket: si ya existía (rechazo por CDR con código distinto de 0),
     * se conserva; si no existía (rechazo antes de ticket), sigue sin existir.
     */
    private function markRejected(Voided $voided, array $response): VoidedStatus
    {
        $voided->sunat_status = VoidedStatus::Rechazada;
        $voided->sunatResponse = $response;
        $voided->save();

        return VoidedStatus::Rechazada;
    }

    /**
     * Marca como dados de baja los comprobantes de la anulación. Se invoca
     * únicamente desde la rama en que el código del CDR es '0'. Filtra por
     * branch_id y company_id leídos del propio Voided recibido, nunca de
     * session() (RESEARCH Pitfall 7): dentro de una capa de servicio el
     * modelo recibido es la fuente fiable del ámbito multi-tenant.
     */
    private function markVoidedInvoices(Voided $voided): void
    {
        foreach ($voided->details as $item) {
            Invoice::where('tipoDoc', $item['tipoDoc'])
                ->where('serie', $item['serie'])
                ->where('correlativo', $item['correlativo'])
                ->where('branch_id', $voided->branch_id)
                ->where('company_id', $voided->company_id)
                ->update(['voided' => true]);
        }
    }
}
