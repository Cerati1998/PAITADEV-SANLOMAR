<?php

namespace App\Services\Sunat\Xml;

use Greenter\Model\Despatch\Despatch;
use Greenter\Model\DocumentInterface;
use Greenter\Model\TimeZonePe;
use Greenter\Xml\Builder\TwigBuilder;
use Greenter\Xml\Filter\FormatFilter;
use ReflectionClass;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

/**
 * Builder de XML para la Guía de Remisión Remitente (tipoDoc 09).
 *
 * Réplica de Greenter\Xml\Builder\DespatchBuilder (mismos filtros, misma zona
 * horaria, misma seleccion de template por version) apuntando primero a un
 * directorio de templates propio, igual que ya se hace en
 * App\Services\Sunat\Transportista\Xml\TransportistaXmlBuilder.
 *
 * Motivo: vendor/ esta en .gitignore y el composer install del deploy lo deja
 * pristine, borrando cualquier ajuste hecho ahi. Asi se perdio en produccion el
 * nodo <cac:LoadingTransportEvent> que SUNAT espera en la guia remitente, sin
 * ninguna senal visible (no aparece en el PDF). Teniendo el template en el repo
 * viaja con el commit y sobrevive a cualquier composer install.
 *
 * El loader encadena [Templates propio, Templates de vendor], asi que solo hace
 * falta versionar los templates que realmente se modifican; el resto sigue
 * resolviendo desde greenter/xml.
 */
class DespatchXmlBuilder
{
    private Environment $twig;

    public function __construct(array $options = [])
    {
        $this->twig = $this->createTwig($options);
    }

    public function build(DocumentInterface $document): string
    {
        /** @var Despatch $despatch */
        $despatch = $document;

        // Misma condicion que Greenter\Xml\Builder\DespatchBuilder::build().
        $template = $despatch->getVersion() === '2022'
            ? 'despatch2022.xml.twig'
            : 'despatch.xml.twig';

        return $this->twig->render($template, [
            'doc' => $document,
        ]);
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

    private function loadFilterAndFunctions(Environment $twig): void
    {
        $formatFilter = new FormatFilter();

        $twig->addFilter(new TwigFilter('n_format', [$formatFilter, 'number']));
        $twig->addFilter(new TwigFilter('n_format_limit', [$formatFilter, 'numberLimit']));
    }
}
