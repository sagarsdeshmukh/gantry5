<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;
use Gantry\Framework\Exporter;
use Symfony\Component\Yaml\Yaml;

class Export extends HtmlController
{
    public function index()
    {
        if (!class_exists('Gantry\Framework\Exporter')) {
            $this->forbidden();
        }

        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('Please enable PHP ZIP extension to use this feature.');
        }

        $exporter = new Exporter;
        $exported = $exporter->all();

        $filename = 'export.zip';
        $tmpname = tempnam(sys_get_temp_dir(), 'zip');

        $zip = new \ZipArchive();
        $zip->open($tmpname, \ZipArchive::CREATE);
        foreach ($exported['positions'] as $position => $data) {
            $zip->addFromString("positions/{$position}.yaml", Yaml::dump($data, 10, 2));
        }
        foreach ($exported['outlines'] as $outline => &$data) {
            if (!empty($data['config'])) {
                foreach ($data['config'] as $name => $config) {
                    if (in_array($name, ['particles', 'page'])) {
                        foreach ($config as $sub => $subconfig) {
                            $zip->addFromString("outlines/{$outline}/{$name}/{$sub}.yaml", Yaml::dump($subconfig, 10, 2));
                        }
                    } else {
                        $zip->addFromString("outlines/{$outline}/{$name}.yaml", Yaml::dump($config, 10, 2));
                    }
                }
            }
            unset($data['config']);
        }
        $zip->addFromString("outlines.yaml", Yaml::dump($exported['outlines'], 10, 2));
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($tmpname));

        @ob_end_clean();
        flush();

        readfile($tmpname);
        unlink($tmpname);

        exit;
    }
}
