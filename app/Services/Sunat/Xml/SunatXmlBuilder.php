<?php

namespace App\Services\Sunat\Xml;

use Greenter\Model\Despatch\Despatch;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Note;
use Greenter\Model\Summary\Summary;
use Greenter\Model\TimeZonePe;
use Greenter\Model\Voided\Voided;
use Greenter\Report\Resolver\InvalidDocumentException;
use Greenter\Xml\Builder\TwigBuilder;
use Greenter\Xml\Filter\FormatFilter;
use Greenter\Xml\Filter\TributoFunction;
use ReflectionClass;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Constructor del XML que se firma y se envia a SUNAT.
 *
 * Reemplaza a los builders de Greenter (InvoiceBuilder, NoteBuilder,
 * DespatchBuilder, VoidedBuilder, SummaryBuilder) unificandolos en una sola
 * clase: todos eran envoltorios finos sobre TwigBuilder que solo elegian el
 * nombre de plantilla. La seleccion de aqui es replica exacta de la de cada uno.
 *
 * Motivo: vendor/ esta en .gitignore y el composer install del deploy lo deja
 * pristine, borrando cualquier ajuste hecho ahi. Asi se perdio en produccion el
 * nodo <cac:LoadingTransportEvent> de la guia remitente, sin ninguna senal
 * visible. Con las plantillas en app/Services/Sunat/Templates viajan con el
 * commit y sobreviven a cualquier composer install.
 *
 * El loader encadena [Templates propio, Templates de vendor] y Twig se queda con
 * la primera coincidencia, asi que basta versionar las plantillas que el sistema
 * realmente emite; el resto (percepciones, retenciones, resumenes, UBL 2.0)
 * sigue resolviendo desde greenter/xml.
 *
 * De Greenter se siguen usando los modelos, la firma (SignedXml), el envio y la
 * lectura del CDR.
 */
class SunatXmlBuilder
{
    private Environment $twig;

    public function __construct(array $options = [])
    {
        $this->twig = $this->createTwig($options);
    }

    public function build(DocumentInterface $document): string
    {
        return $this->twig->render($this->resolveTemplate($document), [
            'doc' => $document,
        ]);
    }

    /**
     * Misma seleccion que hacen los builders de Greenter en su metodo build().
     */
    private function resolveTemplate(DocumentInterface $document): string
    {
        if ($document instanceof Invoice) {
            return 'invoice' . $document->getUblVersion() . '.xml.twig';
        }

        if ($document instanceof Note) {
            $prefijo = $document->getTipoDoc() === '07' ? 'notacr' : 'notadb';

            return $prefijo . $document->getUblVersion() . '.xml.twig';
        }

        if ($document instanceof Despatch) {
            return $document->getVersion() === '2022'
                ? 'despatch2022.xml.twig'
                : 'despatch.xml.twig';
        }

        if ($document instanceof Voided) {
            return 'voided.xml.twig';
        }

        if ($document instanceof Summary) {
            return 'summary.xml.twig';
        }

        throw new InvalidDocumentException('Not found template for ' . get_class($document));
    }

    private function createTwig(array $options): Environment
    {
        $loader = new FilesystemLoader([
            __DIR__ . '/../Templates',
            $this->greenterTemplatesPath(),
        ]);

        $twigEnv = new Environment($loader, $options);
        $this->loadFilterAndFunctions($twigEnv);
        $this->configureTimezone($twigEnv);

        return $twigEnv;
    }

    /**
     * Ruta real de greenter/xml/src/Xml/Templates, resuelta desde la propia
     * clase de Greenter para no hardcodear la ubicacion de vendor/.
     */
    private function greenterTemplatesPath(): string
    {
        $twigBuilderFile = (new ReflectionClass(TwigBuilder::class))->getFileName();

        return dirname($twigBuilderFile, 2) . '/Templates';
    }

    private function configureTimezone(Environment $twig): void
    {
        $extension = $twig->getExtension(CoreExtension::class);
        if ($extension instanceof CoreExtension) {
            $extension->setTimezone(TimeZonePe::DEFAULT);
        }
    }

    /**
     * Filtros de TwigBuilder mas la funcion que InvoiceBuilder y NoteBuilder
     * registran para las plantillas de venta.
     */
    private function loadFilterAndFunctions(Environment $twig): void
    {
        $formatFilter = new FormatFilter();

        $twig->addFilter(new TwigFilter('n_format', [$formatFilter, 'number']));
        $twig->addFilter(new TwigFilter('n_format_limit', [$formatFilter, 'numberLimit']));

        $twig->addFunction(new TwigFunction('getTributoAfect', [TributoFunction::class, 'getByAfectacion']));
    }
}
