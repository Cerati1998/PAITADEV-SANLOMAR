<?php

namespace App\Livewire;

use App\Enums\VoidedStatus;
use App\Models\Voided;
use App\Services\Sunat\UtilService;
use App\Services\Sunat\VoidedService;
use App\Services\Sunat\VoidedTicketFlowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class VoidedTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setAdditionalSelects(['id', 'sunat_ticket', 'sunatResponse']);
        $this->setDefaultSort('id', 'desc');
    }

    // D-07: vía por la que el operador aísla las anulaciones pendientes que
    // debe reconsultar con sendXml(). La closure compone sobre el Builder que
    // ya devuelve builder() (branch_id/company_id/production de la sesión),
    // así que el filtro solo puede estrechar el listado, nunca ensancharlo.
    public function filters(): array
    {
        return [
            SelectFilter::make('Estado Sunat')
                ->options([
                    '' => 'Todos',
                    VoidedStatus::Aceptada->value => VoidedStatus::Aceptada->label(),
                    VoidedStatus::Pendiente->value => VoidedStatus::Pendiente->label(),
                    VoidedStatus::Rechazada->value => VoidedStatus::Rechazada->label(),
                    'sin-estado' => 'SIN ESTADO',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '') {
                        return;
                    }

                    if ($value === 'sin-estado') {
                        $builder->whereNull('sunat_status');

                        return;
                    }

                    $builder->where('sunat_status', $value);
                }),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Fecha Generacion', 'fecGeneracion')
                ->format(function ($value) {
                    return $value->format('d/m/Y');
                })
                ->sortable(),

            Column::make('Fecha Comunicación', 'fecComunicacion')
                ->format(function ($value) {
                    return $value->format('d/m/Y');
                })
                ->sortable(),

            Column::make('correlativo')
                ->format(
                    function ($value) {
                        return 'Comunicación de baja: '.$value;
                    }
                ),

            Column::make('PDF', 'pdf_path')
                ->format(
                    fn ($value, $row) => view('vouchers.partials.pdf', compact('row'))
                )->collapseOnTablet(),

            Column::make('XML', 'xml_path')
                ->format(
                    fn ($value, $row) => view('vouchers.partials.xml', compact('value', 'row'))
                )->collapseOnTablet(),

            Column::make('CDR', 'cdr_path')
                ->format(
                    fn ($value, $row) => view('vouchers.partials.cdr', compact('value', 'row'))
                )->collapseOnTablet(),

            Column::make('Sunat', 'sunat_status')
                ->format(
                    fn ($value, $row) => view('voideds.partials.response', compact('row'))
                )->collapseOnTablet(),
        ];
    }

    //Métodos
    public function downloadPDF(Voided $voided)
    {
        return Storage::download($voided->pdf_path);
    }

    public function downloadXML(Voided $voided)
    {
        return Storage::download($voided->xml_path);
    }

    /**
     * Descarga el ZIP del CDR archivado por VoidedTicketFlowService.
     *
     * La columna CDR de este listado reutiliza el parcial compartido
     * vouchers.partials.cdr, que emite wire:click="downloadCDR(...)" en cuanto
     * la fila tiene cdr_path; sin este método la pulsación reventaba con
     * MethodNotFoundException. Desde que resolveTicket() archiva el ZIP
     * siempre que llegue, prácticamente toda anulación resuelta muestra ese
     * botón, y el CDR es la prueba documental de la baja ante SUNAT.
     *
     * La ruta puede estar poblada sin fichero detrás (cdr_path se asigna antes
     * de escribir el ZIP), así que se comprueba la existencia real en vez de
     * dejar que Storage lance.
     */
    public function downloadCDR(Voided $voided)
    {
        if (empty($voided->cdr_path) || ! Storage::exists($voided->cdr_path)) {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'Sin CDR',
                'text' => 'Esta comunicación de baja todavía no tiene un CDR archivado que descargar.',
            ]);

            return;
        }

        return Storage::download($voided->cdr_path);
    }

    public function sendXml(Voided $voided)
    {
        // D-02: guarda de tres ramas, primero de todo. Las acciones de
        // Livewire son invocables directamente desde el cliente, así que
        // ocultar el botón en la vista no basta: hay que decidir en el
        // servidor si corresponde consultar, avisar o reenviar.
        if (
            ! is_null($voided->sunat_ticket)
            && $voided->sunat_status !== VoidedStatus::Aceptada
            && $voided->sunat_status !== VoidedStatus::Rechazada
        ) {
            // Rama Consultar (D-01/D-02, WEB-02, CR-01): todo ticket vivo cuyo
            // desenlace SUNAT todavía no confirmó se reconsulta, incluida la
            // fila con sunat_status en NULL. La condición excluye los dos
            // desenlaces ya confirmados en vez de enumerar los estados que sí
            // se consultan, para que ningún estado presente ni futuro con
            // ticket vivo quede sin vía de recuperación.
            $util = new UtilService(session('company'));
            $flow = new VoidedTicketFlowService($util);

            try {
                $status = $flow->resolveTicket($voided, $this->cdrPathFor($voided));

                if ($status === VoidedStatus::Pendiente) {
                    $this->dispatchPendingSwal($voided);

                    return;
                }

                $this->showResponse($voided);

                return;
            } catch (\Throwable $e) {
                $status = $flow->classifyFailure($voided, $e);

                if ($status === VoidedStatus::Pendiente) {
                    $this->dispatchPendingSwal($voided);

                    return;
                }

                $this->showResponse($voided);

                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Error al consultar el comprobante',
                    'text' => $e->getMessage(),
                ]);

                return;
            }
        }

        if ($voided->sunat_status === VoidedStatus::Rechazada && ! is_null($voided->sunat_ticket)) {
            // Rama Avisar — desenlace confirmado (D-12, CR-01): sólo una
            // Rechazada que conserva ticket llega aquí. El CDR ya está
            // archivado y el ticket se conserva únicamente para que nadie
            // reenvíe el XML; reconsultar no cambiaría el resultado.
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'Desenlace confirmado',
                'text' => 'SUNAT ya devolvió el CDR rechazando esta comunicación de baja. Reconsultar no cambiaría el resultado, y reenviar el XML devolvería el error 2109 (la comunicación de baja ya fue informada).',
            ]);

            return;
        }

        if (! $this->canResendXml($voided)) {
            // Rama Avisar — no se puede reenviar: el resto (Aceptada, o
            // histórica con respuesta guardada).
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'No se puede reenviar',
                'text' => 'Esta comunicación de baja ya tiene una respuesta de SUNAT registrada. Reenviar el XML devolvería el error 2109 (la comunicación de baja ya fue informada), sin recuperar nada.',
            ]);

            return;
        }

        $directory = $voided->production ? 'sunat/' : 'sunat/beta/';

        $util = new UtilService(session('company'));
        $flow = new VoidedTicketFlowService($util);

        // Fase local (CR-01, gap #12 de 02-VERIFICATION.md): preparar el XML
        // firmado sin tocar ningún estado de SUNAT. Un fallo aquí —columna
        // vacía, fichero ausente, fichero vacío o una regeneración que
        // falla— nunca debe interpretarse como una respuesta de SUNAT.
        $xml = null;

        try {
            // Si no tiene XML o el fichero ya no está en el disco
            // configurado, lo regeneramos. La ruta puede estar poblada sin
            // fichero detrás (purga, cambio de bucket, un Storage::put() que
            // falló en silencio en el envío original): el mismo criterio que
            // downloadCDR() ya aplica en esta misma clase.
            if (empty($voided->xml_path) || ! Storage::exists($voided->xml_path)) {
                $voidedGreenter = (new VoidedService)->getVoided($voided);

                $xml = $util->getXmlSigned($voidedGreenter);
                $voided->hash = $util->getHashSign($xml);
                $voided->xml_path = $directory.'xml/'.$voidedGreenter->getName().'.xml';
                Storage::put($voided->xml_path, $xml);

                $pdf = $util->getReportPdf($voidedGreenter, $voided->hash);
                $voided->pdf_path = $directory.'cpe/'.$voidedGreenter->getName().'.pdf';
                Storage::put($voided->pdf_path, $pdf);

                $voided->save();
            } else {
                $xml = $voided->xml;
            }
        } catch (\Throwable $e) {
            $this->dispatchXmlUnavailableSwal($voided, $e);

            return;
        }

        if (blank($xml)) {
            // Defensa adicional para el disco no-throwing (config/filesystems.php,
            // discos local/public): Storage::get() devuelve null o cadena
            // vacía sin lanzar nada. Con esta guarda, ese valor nunca llega
            // al parámetro string $xml del envío a SUNAT.
            $this->dispatchXmlUnavailableSwal($voided, null);

            return;
        }

        //Enviar a SUNAT
        try {
            // D-04: la ruta del CDR se arma con el RUC de la empresa dueña de
            // la fila (cdrPathFor() lee $voided->company), no con el de la
            // empresa de la sesión.
            $status = $flow->sendAndResolve($voided, $xml, $this->cdrPathFor($voided));

            if ($status === VoidedStatus::Pendiente) {
                $this->dispatchPendingSwal($voided);

                return;
            }

            $this->showResponse($voided);

            return;
        } catch (\Throwable $e) {
            $status = $flow->classifyFailure($voided, $e);

            if ($status === VoidedStatus::Pendiente) {
                $this->dispatchPendingSwal($voided);

                return;
            }

            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error al enviar el comprobante',
                'text' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Único punto de composición del aviso de fila pendiente (GAP 2). Lee el
     * motivo que markPending() acaba de persistir sobre la misma instancia y
     * decide, por ese motivo, si la interfaz puede afirmar que SUNAT sigue
     * procesando o si sólo puede decir que la consulta del ticket no obtuvo
     * una respuesta utilizable — nunca ambas cosas a la vez.
     *
     * Privado: sustituye las cuatro salidas de pendiente de sendXml() (rama
     * Consultar y rama de reenvío, cada una con su try y su catch); un método
     * público sería un endpoint Livewire invocable desde el cliente.
     */
    private function dispatchPendingSwal(Voided $voided): void
    {
        $reason = data_get($voided->sunatResponse, 'pending.reason');
        $code = data_get($voided->sunatResponse, 'pending.code');

        if ($reason === 'codigo_98') {
            // Único motivo que autoriza afirmar progreso: SUNAT respondió con
            // el código que el modal de detalle ya traduce como "aún está
            // procesando la comunicación".
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'Aún en proceso',
                'text' => 'SUNAT todavía no ha terminado de procesar esta comunicación de baja. El ticket sigue guardado; puede volver a consultar más tarde.',
            ]);

            return;
        }

        // Cualquier otro motivo — fallo_transporte, o un motivo ausente por
        // cualquier razón — no puede afirmar progreso alguno: ante la duda,
        // la interfaz no afirma nada que SUNAT no haya comunicado.
        $text = 'La consulta del ticket no obtuvo de SUNAT una respuesta utilizable.';

        if (! is_null($code)) {
            $text .= " Código devuelto: {$code}.";
        }

        $text .= ' El ticket sigue guardado y puede reintentar la consulta.';

        $this->dispatch('swal', [
            'icon' => 'warning',
            'title' => 'No se pudo confirmar el estado',
            'text' => $text,
        ]);
    }

    /**
     * Aviso de fallo local en la preparación del XML firmado (CR-01, gap #12
     * de 02-VERIFICATION.md). Se llama cuando la fase local de sendXml() no
     * pudo obtener un XML utilizable —columna vacía, fichero ausente,
     * fichero vacío, o una excepción al leerlo o al regenerarlo—, es decir,
     * antes de que la comunicación de baja llegara a hablar con SUNAT. No
     * escribe ningún estado en $voided ni delega en el servicio que
     * clasifica las respuestas de SUNAT: el fallo es de la aplicación, no
     * un desenlace del tercero fiscal.
     *
     * El mensaje de la excepción no viaja al swal (T-02-04-03): trae rutas
     * absolutas del bucket de DO Spaces, detalles del WSDL o de SQL que el
     * operador de patio no puede usar. El Throwable completo va al log.
     *
     * Privado: un método público de un componente Livewire es un endpoint
     * invocable desde el cliente.
     */
    private function dispatchXmlUnavailableSwal(Voided $voided, ?\Throwable $e): void
    {
        Log::error('No se pudo preparar el XML firmado de la comunicación de baja', [
            'voided_id' => $voided->id,
            'xml_path' => $voided->xml_path,
            'exception' => $e,
        ]);

        $this->dispatch('swal', [
            'icon' => 'error',
            'title' => 'No se pudo preparar el XML',
            'text' => 'El XML firmado de esta comunicación de baja no está disponible en el almacenamiento. No se envió nada a SUNAT y el estado de la fila no cambió; el detalle quedó registrado.',
        ]);
    }

    public function showResponse(Voided $voided)
    {
        $title = 'Detalle de la comunicación de baja';

        $html = view('voideds.partials.sunatResponse', [
            'document' => $voided,
        ])->render();

        $icon = match ($voided->sunat_status) {
            VoidedStatus::Aceptada => 'info',
            VoidedStatus::Pendiente => 'warning',
            VoidedStatus::Rechazada => 'error',
            null => ($voided->sunatResponse['success'] ?? false) ? 'info' : 'error',
        };

        $this->dispatch('swal', [
            'icon' => $icon,
            'title' => $title,
            'html' => $html,
        ]);
    }

    /**
     * Ruta del ZIP del CDR de esta comunicación de baja. El RUC con que se
     * nombra el fichero sale del JSON company que VoidedObserver::creating()
     * persistió en la fila al crearla (D-04): la empresa dueña del
     * documento, no la empresa de la sesión que dispara la acción.
     */
    private function cdrPathFor(Voided $voided): string
    {
        $directory = $voided->production ? 'sunat/' : 'sunat/beta/';
        $name = $voided->company['ruc'].'-RC-'.$voided->fecGeneracion->format('Ymd').'-'.$voided->correlativo;

        return $directory.'cdr/R-'.$name.'.zip';
    }

    // D-03/D-09/D-13: lista de permitidos, no de bloqueos. Replica hoja por
    // hoja el árbol de voideds/partials/response.blade.php: devuelve true en
    // los dos únicos casos en que reenviar el XML es legítimo. Tras la Fase 2
    // ese árbol tiene dos hojas nuevas que tampoco entran en esta lista: la
    // rama Pendiente y la fila histórica con ticket vivo (sunat_status NULL)
    // ya no abren el detalle, disparan sendXml(). Ambas quedan
    // deliberadamente fuera — devolver false aquí es lo que encamina la fila
    // hacia la rama de reconsulta de sendXml() en vez de hacia el reenvío. La
    // guarda también neutraliza el botón de la columna CDR
    // (vouchers.partials.cdr, compartido por VoucherTable, DespatchTable y
    // DespatchTransportistaTable), que ofrece esta acción a toda fila sin
    // cdr_path sin mirar el estado. Privado porque un método público de un
    // componente Livewire es un endpoint invocable directamente desde el
    // cliente.
    private function canResendXml(Voided $voided): bool
    {
        // Caso 1: rechazada porque el envío inicial nunca llegó a devolver
        // ticket (D-06). sunatResponse sí está poblado aquí (lo escribió
        // markRejected()), por eso no forma parte de esta condición.
        if ($voided->sunat_status === VoidedStatus::Rechazada && is_null($voided->sunat_ticket)) {
            return true;
        }

        // Caso 2: fila histórica anterior a la migración (D-13, doble
        // guardián) sobre la que nunca se envió nada.
        if (is_null($voided->sunat_status) && is_null($voided->sunat_ticket) && empty($voided->sunatResponse)) {
            return true;
        }

        return false;
    }

    public function builder(): Builder
    {
        return Voided::query()
            ->where('branch_id', session('branch')->id)
            ->where('company_id', session('company')->id)
            ->where('production', session('company')->production);
    }
}
