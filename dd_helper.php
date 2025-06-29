<?php

/**
 * dd_helper.php
 *
 * Helper de debug avançado para CodeIgniter 3, inspirado no dd do Laravel.
 * Fornece funções para debug visual, seguro e prático, com recursos para logs, tempo, queries, memória, sessão, request, stack trace, exceções, comparação de variáveis e formatação de bytes.
 *
 * @package    application.helpers
 * @author     Sua Equipe
 * @version    1.0
 * @since      2024-06
 * @license    MIT
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

if (!defined('DD_TIMESTAMP_FORMAT')) {
    define('DD_TIMESTAMP_FORMAT', 'd-M-Y H:i:s');
}

/**
 * Grava uma entrada de log de debug em arquivo (apenas ambiente development).
 *
 * @param string $message Mensagem principal do log
 * @param array $context  Contexto adicional (opcional)
 * @return void
 */
if (!function_exists('ddLog')) {
    function ddLog($message, $context = [])
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
            return;
        }

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        $file = isset($bt['file']) ? basename($bt['file']) : '';
        $line = isset($bt['line']) ? $bt['line'] : '';

        $logEntry = [
            'timestamp' => date(DD_TIMESTAMP_FORMAT),
            'file' => $file,
            'line' => $line,
            'message' => $message,
            'context' => $context
        ];

        $logPath = APPPATH . 'logs/debug_' . date('Y-m-d') . '.log';
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL;

        if (!file_exists(dirname($logPath))) {
            mkdir(dirname($logPath), 0755, true);
        }

        file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Marca ou finaliza um timer de execução (benchmark simples).
 *
 * @param string $name Nome do timer
 * @return string Mensagem de início ou tempo decorrido
 */
if (!function_exists('ddTimer')) {
    function ddTimer($name = 'default')
    {
        static $timers = [];

        if (!isset($timers[$name])) {
            $timers[$name] = microtime(true);
            return "Timer '$name' iniciado";
        } else {
            $elapsed = microtime(true) - $timers[$name];
            unset($timers[$name]);
            return "Timer '$name': " . number_format($elapsed * 1000, 2) . "ms";
        }
    }
}

/**
 * Exibe informações de queries SQL executadas (CodeIgniter).
 *
 * @param bool $showLastQuery Se true, mostra a última query
 * @return void
 */
if (!function_exists('ddQuery')) {
    function ddQuery($showLastQuery = true)
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
            return;
        }

        $CI = &get_instance();
        $CI->load->database();

        $queries = [];
        if ($showLastQuery) {
            $queries[] = $CI->db->last_query();
        }

        if (isset($CI->db->queries) && is_array($CI->db->queries)) {
            $queries = array_merge($queries, $CI->db->queries);
        }

        dd([
            'last_query' => $CI->db->last_query(),
            'all_queries' => array_unique($queries),
            'query_count' => count($queries)
        ]);
    }
}

/**
 * Exibe informações de uso de memória.
 *
 * @param string $label Rótulo opcional
 * @return void
 */
if (!function_exists('ddMemory')) {
    function ddMemory($label = '')
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
            return;
        }

        $memory = [
            'current' => memory_get_usage(true),
            'current_formatted' => formatBytes(memory_get_usage(true)),
            'peak' => memory_get_peak_usage(true),
            'peak_formatted' => formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit')
        ];

        if ($label) {
            $memory['label'] = $label;
        }

        dd($memory);
    }
}

/**
 * Exibe dados da sessão atual ou de uma chave específica.
 *
 * @param string|null $key Chave da sessão (opcional)
 * @return void
 */
if (!function_exists('ddSession')) {
    function ddSession($key = null)
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
            return;
        }

        $CI = &get_instance();
        $CI->load->library('session');

        if ($key) {
            dd([
                'session_key' => $key,
                'value' => $CI->session->userdata($key),
                'all_session_data' => $CI->session->all_userdata()
            ]);
        } else {
            dd($CI->session->all_userdata());
        }
    }
}

/**
 * Exibe informações da requisição HTTP atual.
 *
 * @return void
 */
if (!function_exists('ddRequest')) {
    function ddRequest()
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
            return;
        }

        $CI = &get_instance();

        $request = [
            'method' => $CI->input->method(),
            'uri' => $CI->uri->uri_string(),
            'get' => $CI->input->get(),
            'post' => $CI->input->post(),
            'headers' => $CI->input->request_headers(),
            'user_agent' => $CI->input->user_agent(),
            'ip_address' => $CI->input->ip_address(),
            'is_ajax' => $CI->input->is_ajax_request()
        ];

        dd($request);
    }
}

/**
 * Exibe o stack trace do ponto de chamada.
 *
 * @param int $limit Limite de passos do trace
 * @return void
 */
if (!function_exists('ddTrace')) {
    function ddTrace($limit = 10)
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
            return;
        }

        if (!is_int($limit) || $limit <= 0) {
            dd(['error' => 'O parâmetro $limit deve ser um inteiro positivo']);
            return;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit);
        $formattedTrace = [];

        foreach ($trace as $i => $call) {
            $formattedTrace[] = [
                'step' => $i + 1,
                'file' => isset($call['file']) ? basename($call['file']) : '[internal]',
                'line' => isset($call['line']) ? $call['line'] : '',
                'function' => isset($call['function']) ? $call['function'] : '',
                'class' => isset($call['class']) ? $call['class'] : '',
                'type' => isset($call['type']) ? $call['type'] : ''
            ];
        }

        dd($formattedTrace);
    }
}

/**
 * Exibe detalhes de uma exceção Throwable de forma visual.
 *
 * @param Throwable $exception Exceção capturada
 * @param bool $showTrace Se true, mostra o stack trace
 * @return void
 */
if (!function_exists('ddException')) {
    function ddException($exception, $showTrace = true)
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
            return;
        }

        if (!($exception instanceof Throwable)) {
            dd(['error' => 'Parâmetro deve ser uma instância de Throwable']);
            return;
        }

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        $file = isset($bt['file']) ? $bt['file'] : '';
        $line = isset($bt['line']) ? $bt['line'] : '';
        $timestamp = date(DD_TIMESTAMP_FORMAT);

        echo '<style>
        body{background:#1e1e1e;color:#d4d4d4;font-family:"Fira Code",Monaco,Consolas,monospace;}
        .dd-exception{background:#2d1b1b;color:#fff;padding:20px;margin:16px 0;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.3);position:relative;border-left:4px solid #ff5555;}
        .dd-exception pre{margin:0;white-space:pre-wrap;word-break:break-all;line-height:1.4;}
        .dd-meta{color:#ff5555;font-size:13px;margin-bottom:12px;padding:8px 12px;background:rgba(255,85,85,0.1);border-radius:6px;position:relative;}
        .dd-exception-title{color:#ff5555;font-size:16px;font-weight:bold;margin-bottom:12px;}
        .dd-exception-msg{color:#f8f8f2;font-size:14px;margin-bottom:8px;padding:12px;background:rgba(255,85,85,0.05);border-radius:6px;}
        .dd-exception-details{margin-bottom:12px;}
        .dd-exception-trace{background:rgba(0,0,0,0.3);padding:12px;border-radius:6px;max-height:400px;overflow-y:auto;}
        .dd-copy-btn{position:absolute;top:50%;right:12px;transform:translateY(-50%);background:#ff5555;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:11px;transition:background 0.2s;}
        .dd-copy-btn:hover{background:#ff7777;}
        </style>';

        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var copyBtn = document.getElementById("dd-exception-copy-btn");
            if(copyBtn){
                copyBtn.addEventListener("click", function(){
                    var txt = document.getElementById("dd-exception-content").innerText;
                    navigator.clipboard.writeText(txt).then(function(){
                        copyBtn.textContent = "✓ Copiado!";
                        setTimeout(function(){copyBtn.textContent = "📋 Copiar";}, 2000);
                    });
                });
            }
        });
        </script>';

        echo '<div class="dd-exception">';
        echo '<div class="dd-meta">🚨 Exception Debug | 📁 <b>' . htmlspecialchars(basename($file)) . '</b> | 📍 Linha <b>' . $line . '</b> | 🕒 ' . $timestamp . '<button id="dd-exception-copy-btn" class="dd-copy-btn">📋 Copiar</button></div>';
        echo '<div id="dd-exception-content">';

        echo '<div class="dd-exception-title">🔥 ' . get_class($exception) . '</div>';
        echo '<div class="dd-exception-msg">💬 Mensagem: ' . htmlspecialchars($exception->getMessage()) . '</div>';

        echo '<div class="dd-exception-details">';
        echo '<strong>📂 Arquivo:</strong> ' . htmlspecialchars($exception->getFile()) . '<br>';
        echo '<strong>📍 Linha:</strong> ' . $exception->getLine() . '<br>';
        echo '<strong>🔢 Código:</strong> ' . $exception->getCode() . '<br>';
        echo '</div>';

        if ($showTrace) {
            echo '<div class="dd-exception-trace">';
            echo '<strong>📋 Stack Trace:</strong><br><br>';

            $trace = $exception->getTrace();
            foreach ($trace as $i => $step) {
                echo '<div style="margin-bottom:8px;padding:8px;background:rgba(255,255,255,0.05);border-radius:4px;">';
                echo '<strong>#' . $i . '</strong> ';

                if (isset($step['file'])) {
                    echo htmlspecialchars(basename($step['file'])) . ':' . (isset($step['line']) ? $step['line'] : '?');
                } else {
                    echo '[internal function]';
                }

                if (isset($step['class']) && isset($step['function'])) {
                    echo '<br>    ' . $step['class'] . $step['type'] . $step['function'] . '()';
                } elseif (isset($step['function'])) {
                    echo '<br>    ' . $step['function'] . '()';
                }
                echo '</div>';
            }
            echo '</div>';
        }

        echo '</div></div>';
        exit;
    }
}

/**
 * Compara duas variáveis e mostra diferenças e igualdade.
 *
 * @param mixed $var1 Primeira variável
 * @param mixed $var2 Segunda variável
 * @param string $label1 Rótulo da primeira variável
 * @param string $label2 Rótulo da segunda variável
 * @return void
 */
if (!function_exists('ddCompare')) {
    function ddCompare($var1, $var2, $label1 = 'Variável 1', $label2 = 'Variável 2')
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
            return;
        }

        dd([
            $label1 => $var1,
            $label2 => $var2,
            'são_iguais' => $var1 === $var2,
            'são_iguais_sem_tipo' => $var1 == $var2,
            'diff' => array_diff_assoc(
                is_array($var1) ? $var1 : [$var1],
                is_array($var2) ? $var2 : [$var2]
            )
        ]);
    }
}

/**
 * Formata bytes em unidade legível (B, KB, MB, ...).
 *
 * @param int|float $size Tamanho em bytes
 * @param int $precision Casas decimais
 * @return string
 */
if (!function_exists('formatBytes')) {
    function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision) . ' ' . $units[$i];
    }
}

/**
 * Renderiza variáveis de forma visual e interativa (recursivo, arrays, objetos, etc).
 *
 * @param mixed $var Variável a ser exibida
 * @param int $level Nível de profundidade (interno)
 * @param array $objectCache Cache para detecção de recursão
 * @return void
 */
if (!function_exists('ddRenderVar')) {
    function ddRenderVar($var, $level = 0, &$objectCache = [])
    {
        $id = uniqid('dd_', true);
        $indent = str_repeat('  ', $level);

        if (is_object($var)) {
            $objectHash = spl_object_hash($var);
            if (in_array($objectHash, $objectCache)) {
                echo "$indent<span style='color:#ff5555;'>[RECURSÃO DETECTADA]</span>";
                return;
            }
            $objectCache[] = $objectHash;
        }

        if (is_array($var)) {
            $count = count($var);
            echo "$indent<span class='dd-toggle' data-target='$id'>[+]</span> <span class='dd-arr-obj'>array($count)</span> <div id='$id' class='dd-collapsed' style='display:none;margin-left:18px;'>";

            if ($level > 10) {
                echo "$indent<span style='color:#ff5555;'>[NÍVEL MÁXIMO DE ANINHAMENTO ATINGIDO]</span>";
            } else {
                foreach ($var as $k => $v) {
                    echo "$indent<div style='margin-bottom:2px;'><span class='dd-key'>[" . htmlspecialchars($k) . "]</span> => ";
                    ddRenderVar($v, $level + 1, $objectCache);
                    echo '</div>';
                }
            }
            echo "$indent</div>";
        } elseif (is_object($var)) {
            $class = get_class($var);

            if ($var instanceof Throwable) {
                echo "$indent<span class='dd-toggle' data-target='$id'>[+]</span> <span style='color:#ff5555;'>🚨 Exception($class)</span> <div id='$id' class='dd-collapsed' style='display:none;margin-left:18px;'>";
                echo "$indent<div style='margin-bottom:2px;'><span class='dd-key'>[message]</span> => <span style='color:#ff5555;'>\"" . htmlspecialchars($var->getMessage()) . "\"</span></div>";
                echo "$indent<div style='margin-bottom:2px;><span class='dd-key'>[code]</span> => <span style='color:#bd93f9;'>" . $var->getCode() . "</span></div>";
                echo "$indent<div style='margin-bottom:2px;'><span class='dd-key'>[file]</span> => <span style='color:#50fa7b;'>\"" . htmlspecialchars($var->getFile()) . "\"</span></div>";
                echo "$indent<div style='margin-bottom:2px;'><span class='dd-key'>[line]</span> => <span style='color:#bd93f9;'>" . $var->getLine() . "</span></div>";
                echo "$indent<div style='margin-bottom:2px;'><span class='dd-key'>[trace]</span> => ";
                $trace = $var->getTrace();
                ddRenderVar($trace, $level + 1, $objectCache);
                echo '</div>';
                echo "$indent</div>";
            } else {
                $props = get_object_vars($var);
                $methods = get_class_methods($var);
                echo "$indent<span class='dd-toggle' data-target='$id'>[+]</span> <span class='dd-arr-obj'>object($class)</span> <div id='$id' class='dd-collapsed' style='display:none;margin-left:18px;'>";
                if (!empty($props)) {
                    echo "$indent<div style='margin-bottom:4px;'><span class='dd-key'>[Propriedades]</span></div>";
                    foreach ($props as $k => $v) {
                        echo "$indent<div style='margin-bottom:2px;'><span class='dd-key'>[" . htmlspecialchars($k) . "]</span> => ";
                        ddRenderVar($v, $level + 1, $objectCache);
                        echo '</div>';
                    }
                } else {
                    echo "$indent<span style='color:#888;'>[sem propriedades públicas]</span><br>";
                }
                if (!empty($methods)) {
                    echo "$indent<div style='margin-bottom:4px;'><span class='dd-key'>[Métodos Públicos (" . count($methods) . ")]</span></div>";
                    $methodId = uniqid('dd_', true);
                    echo "$indent<span class='dd-toggle' data-target='$methodId'>[+]</span> <span class='dd-arr-obj'>métodos</span> <div id='$methodId' class='dd-collapsed' style='display:none;margin-left:18px;'>";
                    foreach ($methods as $method) {
                        echo "$indent<div style='margin-bottom:2px;'><span style='color:#8be9fd;'>$method()</span></div>";
                    }
                    echo "$indent</div>";
                } else {
                    echo "$indent<span style='color:#888;'>[sem métodos públicos]</span>";
                }
                echo "$indent</div>";
            }

            if (($key = array_search($objectHash, $objectCache)) !== false) {
                unset($objectCache[$key]);
            }
        } else {
            if (is_bool($var)) {
                echo "<span style='color:#ff79c6;'>" . ($var ? 'true' : 'false') . "</span>";
            } elseif (is_null($var)) {
                echo "<span style='color:#6272a4;'>null</span>";
            } elseif (is_string($var)) {
                $len = mb_strlen($var);
                echo "<span style='color:#6272a4;'>(string[$len])</span> ";
                if (preg_match('/<[^>]+>/', $var)) {
                    echo "<span style='color:#f8f8f2;'>$var</span>";
                } else {
                    echo "<span style='color:#50fa7b;'>\"" . htmlspecialchars($var) . "\"</span>";
                }
            } elseif (is_numeric($var)) {
                echo "<span style='color:#bd93f9;'>" . htmlspecialchars($var) . "</span>";
            } elseif (is_resource($var)) {
                $resourceType = get_resource_type($var);
                echo "<span style='color:#ffb86c;'>resource($resourceType)</span>";
            } elseif (is_callable($var)) {
                if (is_string($var)) {
                    echo "<span style='color:#8be9fd;'>callable(function: \"$var\")</span>";
                } elseif (is_array($var) && count($var) == 2) {
                    $class = is_object($var[0]) ? get_class($var[0]) : $var[0];
                    echo "<span style='color:#8be9fd;'>callable(method: \"$class::{$var[1]}\")</span>";
                } elseif ($var instanceof Closure) {
                    echo "<span style='color:#8be9fd;'>callable(Closure) [não serializável]</span>";
                } else {
                    echo "<span style='color:#8be9fd;'>callable [tipo desconhecido]</span>";
                }
            } else {
                $type = gettype($var);
                echo "<span style='color:#f8f8f2;'>($type) " . htmlspecialchars(print_r($var, true)) . "</span>";
            }
        }
    }
}

/**
 * Exibe variáveis e interrompe a execução (dump and die).
 *
 * @param mixed ...$vars Variáveis a serem exibidas
 * @return void
 */
if (!function_exists('dd')) {
    function dd(...$vars)
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
            return;
        }

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        $file = isset($bt['file']) ? $bt['file'] : '';
        $line = isset($bt['line']) ? $bt['line'] : '';
        $timestamp = date(DD_TIMESTAMP_FORMAT);

        echo '<style>
        body{background:#1e1e1e;color:#d4d4d4;font-family:"Fira Code",Monaco,Consolas,monospace;}
        .dd-debug{background:#282c34;color:#fff;padding:20px;margin:16px 0;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.3);position:relative;border-left:4px solid #50fa7b;}
        .dd-debug pre{margin:0;white-space:pre-wrap;word-break:break-all;line-height:1.4;}
        .dd-meta{color:#ffb86c;font-size:13px;margin-bottom:12px;padding:8px 12px;background:rgba(255,184,108,0.1);border-radius:6px;position:relative;}
        .dd-type{color:#8be9fd;font-size:12px;margin-bottom:4px;font-weight:bold;}
        .dd-space{height:16px;border-bottom:1px solid #44475a;margin-bottom:16px;}
        .dd-toggle{cursor:pointer;color:#50fa7b;font-weight:bold;transition:color 0.2s;}
        .dd-toggle:hover{color:#5af78e;}
        .dd-arr-obj{color:#f1fa8c;font-weight:bold;}
        .dd-key{color:#bd93f9;font-weight:bold;}
        .dd-copy-btn{position:absolute;top:50%;right:12px;transform:translateY(-50%);background:#6272a4;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:11px;transition:background 0.2s;}
        .dd-copy-btn:hover{background:#7289da;}
        .dd-collapsed{margin-top:4px;}
        </style>';

        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".dd-toggle").forEach(function(btn){
                btn.addEventListener("click", function(){
                    var tgt = document.getElementById(btn.getAttribute("data-target"));
                    if (tgt.style.display === "none") {
                        tgt.style.display = "block";
                        btn.textContent = "[-]";
                    } else {
                        tgt.style.display = "none";
                        btn.textContent = "[+]";
                    }
                });
            });
            
            var copyBtn = document.getElementById("dd-copy-btn");
            if(copyBtn){
                copyBtn.addEventListener("click", function(){
                    var txt = document.getElementById("dd-content").innerText;
                    navigator.clipboard.writeText(txt).then(function(){
                        copyBtn.textContent = "✓ Copiado!";
                        setTimeout(function(){copyBtn.textContent = "📋 Copiar";}, 2000);
                    });
                });
            }
        });
        </script>';

        echo '<div class="dd-debug">';
        echo '<div class="dd-meta">📁 <b>' . htmlspecialchars(basename($file)) . '</b> | 📍 Linha <b>' . $line . '</b> | 🕒 ' . $timestamp . '<button id="dd-copy-btn" class="dd-copy-btn">📋 Copiar</button></div>';
        echo '<div id="dd-content">';

        foreach ($vars as $i => $var) {
            if ($i > 0) {
                echo '<div class="dd-space"></div>';
            }
            $type = gettype($var);
            echo '<div class="dd-type">🔍 [' . ($i + 1) . '] Tipo: <b>' . $type . '</b></div>';
            echo '<pre style="background:none;border:none;padding:0;margin:0;">';
            ddRenderVar($var);
            echo '</pre>';
        }
        echo '</div></div>';
        exit;
    }
}

/**
 * Exibe variáveis sem interromper a execução.
 *
 * @param mixed ...$vars Variáveis a serem exibidas
 * @return void
 */
if (!function_exists('d')) {
    function d(...$vars)
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
            return;
        }

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        $file = isset($bt['file']) ? $bt['file'] : '';
        $line = isset($bt['line']) ? $bt['line'] : '';
        $timestamp = date(DD_TIMESTAMP_FORMAT);

        echo '<style>
        body{background:#1e1e1e;color:#d4d4d4;font-family:"Fira Code",Monaco,Consolas,monospace;}
        .dd-debug{background:#282c34;color:#fff;padding:20px;margin:16px 0;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.3);position:relative;border-left:4px solid #50fa7b;}
        .dd-debug pre{margin:0;white-space:pre-wrap;word-break:break-all;line-height:1.4;}
        .dd-meta{color:#ffb86c;font-size:13px;margin-bottom:12px;padding:8px 12px;background:rgba(255,184,108,0.1);border-radius:6px;position:relative;}
        .dd-type{color:#8be9fd;font-size:12px;margin-bottom:4px;font-weight:bold;}
        .dd-space{height:16px;border-bottom:1px solid #44475a;margin-bottom:16px;}
        .dd-toggle{cursor:pointer;color:#50fa7b;font-weight:bold;transition:color 0.2s;}
        .dd-toggle:hover{color:#5af78e;}
        .dd-arr-obj{color:#f1fa8c;font-weight:bold;}
        .dd-key{color:#bd93f9;font-weight:bold;}
        .dd-copy-btn{position:absolute;top:50%;right:12px;transform:translateY(-50%);background:#6272a4;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:11px;transition:background 0.2s;}
        .dd-copy-btn:hover{background:#7289da;}
        .dd-collapsed{margin-top:4px;}
        </style>';

        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".dd-toggle").forEach(function(btn){
                btn.addEventListener("click", function(){
                    var tgt = document.getElementById(btn.getAttribute("data-target"));
                    if (tgt.style.display === "none") {
                        tgt.style.display = "block";
                        btn.textContent = "[-]";
                    } else {
                        tgt.style.display = "none";
                        btn.textContent = "[+]";
                    }
                });
            });
            
            var copyBtn = document.getElementById("dd-copy-btn");
            if(copyBtn){
                copyBtn.addEventListener("click", function(){
                    var txt = document.getElementById("dd-content").innerText;
                    navigator.clipboard.writeText(txt).then(function(){
                        copyBtn.textContent = "✓ Copiado!";
                        setTimeout(function(){copyBtn.textContent = "📋 Copiar";}, 2000);
                    });
                });
            }
        });
        </script>';

        echo '<div class="dd-debug">';
        echo '<div class="dd-meta">📁 <b>' . htmlspecialchars(basename($file)) . '</b> | 📍 Linha <b>' . $line . '</b> | 🕒 ' . $timestamp . '<button id="dd-copy-btn" class="dd-copy-btn">📋 Copiar</button></div>';
        echo '<div id="dd-content">';

        foreach ($vars as $i => $var) {
            if ($i > 0) {
                echo '<div class="dd-space"></div>';
            }
            $type = gettype($var);
            echo '<div class="dd-type">🔍 [' . ($i + 1) . '] Tipo: <b>' . $type . '</b></div>';
            echo '<pre style="background:none;border:none;padding:0;margin:0;">';
            ddRenderVar($var);
            echo '</pre>';
        }
        echo '</div></div>';
    }
}

/**
 * Exibe variáveis em formato JSON formatado e interrompe a execução.
 *
 * @param mixed ...$vars Variáveis a serem exibidas
 * @return void
 */
if (!function_exists('ddJson')) {
    function ddJson(...$vars)
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'development') {
            return;
        }

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        $file = isset($bt['file']) ? $bt['file'] : '';
        $line = isset($bt['line']) ? $bt['line'] : '';
        $timestamp = date(DD_TIMESTAMP_FORMAT);

        $out = [
            'debug_info' => [
                'arquivo' => basename($file),
                'linha' => $line,
                'timestamp' => $timestamp
            ],
            'data' => []
        ];

        foreach ($vars as $i => $var) {
            $out['data'][] = [
                'index' => $i + 1,
                'tipo' => gettype($var),
                'valor' => $var
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
