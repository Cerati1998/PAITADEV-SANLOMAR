<?php

namespace App\Services\Sunat\Transportista\Xml;

use Greenter\Model\DocumentInterface;
use Greenter\Model\TimeZonePe;
use Greenter\Xml\Filter\FormatFilter;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

/**
 * Builder de XML para la Guía de Remisión Transportista.
 *
 * Réplica deliberada de Greenter\Xml\Builder\TwigBuilder (mismos filtros,
 * misma zona horaria) apuntando a un directorio de templates propio, porque
 * Greenter\Xml\Builder\DespatchBuilder no acepta un template alternativo por
 * tipoDoc — ver guia-transportista.md.
 */
class TransportistaXmlBuilder
{
    private Environment $twig;

    public function __construct(array $options = [])
    {
        $this->twig = $this->createTwig($options);
    }

    public function build(DocumentInterface $document): string
    {
        return $this->twig->render('despatchTransportista.xml.twig', [
            'doc' => $document,
        ]);
    }

    private function createTwig(array $options): Environment
    {
        $loader = new FilesystemLoader(__DIR__ . '/../Templates');

        $twigEnv = new Environment($loader, $options);
        $this->loadFilterAndFunctions($twigEnv);
        $this->configureTimezone($twigEnv);

        return $twigEnv;
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
