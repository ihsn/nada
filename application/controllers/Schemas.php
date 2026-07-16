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
}
