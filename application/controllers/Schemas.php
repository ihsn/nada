<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Schemas extends CI_Controller {

    public function serve($filename = '')
    {
        // Only allow safe filenames: letters, digits, hyphens, underscores, must end in .json
        if (!preg_match('/^[a-zA-Z0-9_-]+\.json$/', $filename)) {
            $this->output->set_status_header(404);
            return;
        }

        $path = APPPATH . 'schemas/' . $filename;

        if (!file_exists($path)) {
            $this->output->set_status_header(404);
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_header('Access-Control-Allow-Origin: *')
            ->set_output(file_get_contents($path));
    }

    /**
     * Serve OpenAPI YAML with external schema $ref values rewritten to
     * base_url('index.php/schemas/...') so ReDoc loads schemas from
     * application/schemas/ on the same host and protocol as this request.
     *
     * @param string $doc catalog-admin|catalog
     */
    public function openapi($doc = '')
    {
        $allowed = array(
            'catalog-admin' => FCPATH . 'api-documentation/catalog-admin/openapi.yaml',
            'catalog'       => FCPATH . 'api-documentation/catalog/openapi.yaml',
        );

        if (!isset($allowed[$doc]) || !is_file($allowed[$doc])) {
            $this->output->set_status_header(404);
            return;
        }

        $yaml = file_get_contents($allowed[$doc]);
        if ($yaml === false) {
            $this->output->set_status_header(500);
            $this->output->set_output('Error: openapi.yaml not found');
            return;
        }

        $schemas_base = base_url('index.php/schemas/');
        if (is_https()) {
            $schemas_base = preg_replace('#^http://#i', 'https://', $schemas_base);
        }

        $yaml = preg_replace_callback(
            '/(\$ref:\s*[\'"])(?:[^\'\"]*\/)?([a-zA-Z0-9_-]+\.json)(#[^\'"]*)?([\'"])/',
            function ($m) use ($schemas_base) {
                return $m[1] . $schemas_base . $m[2] . ($m[3] ?? '') . $m[4];
            },
            $yaml
        );

        $this->output
            ->set_header('Content-Type: application/x-yaml; charset=utf-8')
            ->set_header('Access-Control-Allow-Origin: *')
            ->set_output($yaml);
    }
}
