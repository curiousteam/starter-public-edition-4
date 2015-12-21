<?php defined('BASEPATH') || exit('No direct script access allowed');

/**
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>, 2015
 * @license The MIT License, http://opensource.org/licenses/MIT
 */

require_once COMMONPATH.'parser_lex_extensions/Parser_Lex_Extensions.php';

class CI_Parser_lex extends CI_Parser_driver {

    protected $config;
    private $ci;
    private $extensions;

    public function initialize()
    {
        $php_min = '5.3';

        if (!is_php($php_min))
        {
            throw new Exception('CI_Parser_lex: Requires PHP '.$php_min.' or above.');
        }

        $this->ci = get_instance();

        $this->extensions = new Parser_Lex_Extensions;

        // Default configuration options.

        $this->config = array(
            'cumulative_noparse' => false,
            'scope_glue' => '.',
            'allow_php' => false,
        );

        if ($this->ci->config->load('parser_lex', TRUE, TRUE))
        {
            $this->config = array_merge($this->config, $this->ci->config->item('parser_lex'));
        }

        // Injecting configuration options directly.

        if (isset($this->_parent) && !empty($this->_parent->params) && is_array($this->_parent->params))
        {
            $this->config = array_merge($this->config, $this->_parent->params);

            if (array_key_exists('parser_driver', $this->config))
            {
                unset($this->config['parser_driver']);
            }
        }

        log_message('info', 'CI_Parser_lex Class Initialized');
    }

    public function parse($template, $data = array(), $return = FALSE, $config = array())
    {
        if (!is_array($config))
        {
            $config = array();
        }

        $config = array_merge($this->config, $config);

        $config['cumulative_noparse'] = !empty($config['cumulative_noparse']);

        $ci = $this->ci;
        $is_mx = false;

        if (!$return || !$config['full_path'])
        {
            list($ci, $is_mx) = $this->detect_mx();
        }

        if (!$config['full_path'])
        {
            $template = $ci->load->path($template);
        }

        $parser_reflection = new ReflectionClass('Lex\Parser');
        $parser = $parser_reflection->newInstance();
        $parser_class_name = get_class($parser);

        $parser->scopeGlue($config['scope_glue']);
        $parser->cumulativeNoparse($config['cumulative_noparse']);

        if (!is_array($data))
        {
            if (empty($data))
            {
                $data = array();
            }
            else
            {
                $data = (array) $data;
            }
        }

        $data = array_merge($data, $ci->load->_ci_cached_vars);

        $this->extensions->options = $config;
        $this->extensions->options['data'] = & $data;

        $template = $parser->parse(@ file_get_contents($template), $data, array($this->extensions, 'parser_callback'), $config['allow_php']);

        if ($config['cumulative_noparse']) {
            $template = $parser_class_name::injectNoparse($template);
        }

        return $this->output($template, $return, $ci, $is_mx);
    }

    public function parse_string($template, $data = array(), $return = FALSE, $config = array())
    {
        if (!is_array($config))
        {
            $config = array();
        }

        $config = array_merge($this->config, $config);

        $config['cumulative_noparse'] = !empty($config['cumulative_noparse']);

        $ci = $this->ci;
        $is_mx = false;

        if (!$return)
        {
            list($ci, $is_mx) = $this->detect_mx();
        }

        $parser_reflection = new ReflectionClass('Lex\Parser');
        $parser = $parser_reflection->newInstance();
        $parser_class_name = get_class($parser);

        $parser->scopeGlue($config['scope_glue']);
        $parser->cumulativeNoparse($config['cumulative_noparse']);

        if (!is_array($data))
        {
            if (empty($data))
            {
                $data = array();
            }
            else
            {
                $data = (array) $data;
            }
        }

        $data = array_merge($data, $ci->load->_ci_cached_vars);

        $this->extensions->options = $config;
        $this->extensions->options['data'] = & $data;

        $template = $parser->parse($template, $data, array($this->extensions, 'parser_callback'), $config['allow_php']);

        if ($config['cumulative_noparse']) {
            $template = $parser_class_name::injectNoparse($template);
        }

        return $this->output($template, $return, $ci, $is_mx);
    }

}