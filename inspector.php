<?php
// inspector.php - herramienta de inspección PHP con soporte MVC + búsqueda
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    function listDir($dir) {
        $items = array_diff(scandir($dir), ['.', '..']);
        $result = [];
        foreach ($items as $item) {
            $path = "$dir/$item";
            $result[] = [
                'name' => $item,
                'path' => str_replace(realpath(__DIR__) . '/', '', realpath($path)),
                'type' => is_dir($path) ? 'dir' : 'file'
            ];
        }
        return $result;
    }

    function analyzeFile($filePath) {
        $absPath = realpath(__DIR__ . '/' . $filePath);
        if (!file_exists($absPath)) return ['error' => 'File not found'];

        $content = file_get_contents($absPath);
        
        preg_match_all('/\bclass\s+(\w+)/', $content, $classes);
        preg_match_all('/\bfunction\s+(\w+)/', $content, $functions);
        preg_match_all('/(?:include|require)(_once)?\s*\(?[\'\"](.+?)[\'\"]\)?\s*;/', $content, $matches);
        preg_match_all('/(SELECT|INSERT|UPDATE|DELETE)\s.+?;/is', $content, $queries);

        $refers = [];
        $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
        foreach ($allFiles as $file) {
            if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $scan = file_get_contents($file);
                if (strpos($scan, $filePath) !== false && $filePath !== basename($file)) {
                    $refers[] = str_replace(__DIR__ . '/', '', $file);
                }
            }
        }

        // Detect related controller and model
        $mvc = ["controller" => null, "model" => null];
        if (strpos($filePath, 'views') !== false) {
            $name = pathinfo($filePath, PATHINFO_FILENAME);
            $mvc["controller"] = findSimilar("controllers", $name . "Controller.php");
            $mvc["model"] = findSimilar("models", $name . "Model.php");
        }

        return [
            'content' => htmlspecialchars($content),
            'classes' => $classes[1],
            'functions' => $functions[1],
            'includes' => $matches[2],
            'sql_queries' => array_map('trim', $queries[0]),
            'referenced_by' => $refers,
            'mvc' => $mvc
        ];
    }

    function findSimilar($dir, $filename) {
        $path = __DIR__ . "/$dir";
        if (!is_dir($path)) return null;
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($rii as $file) {
            if ($file->isFile() && stripos($file->getFilename(), $filename) !== false) {
                return str_replace(__DIR__ . '/', '', $file->getRealPath());
            }
        }
        return null;
    }

    if ($_GET['action'] === 'list') {
        $dir = isset($_GET['dir']) ? realpath(__DIR__ . '/' . $_GET['dir']) : __DIR__;
        echo json_encode(listDir($dir));

    } elseif ($_GET['action'] === 'analyze' && isset($_GET['file'])) {
        echo json_encode(analyzeFile($_GET['file']));

    } elseif ($_GET['action'] === 'search' && isset($_GET['term'])) {
        $term = strtolower($_GET['term']);
        $matches = [];
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
        foreach ($rii as $file) {
            if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $path = str_replace(__DIR__ . '/', '', $file->getRealPath());
                $content = file_get_contents($file);
                if (stripos($path, $term) !== false || stripos($content, $term) !== false) {
                    $matches[] = $path;
                }
            }
        }
        echo json_encode($matches);
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Inspector PHP MVC</title>
<style>
body { display: flex; height: 100vh; margin: 0; font-family: sans-serif; }
#tree, #fileContent, #context { padding: 10px; overflow: auto; }
#tree { width: 25%; background: #f1f1f1; border-right: 1px solid #ccc; }
#fileContent { width: 45%; white-space: pre-wrap; font-family: monospace; border-right: 1px solid #ccc; }
#context { width: 30%; background: #fafafa; }
ul { list-style: none; padding-left: 15px; }
.folder { font-weight: bold; cursor: pointer; }
.sql { background: #eef; padding: 3px; margin-bottom: 3px; display: block; }
</style>
</head>
<body>
<div id="tree">
    <input type="text" id="search" placeholder="Buscar archivo o función..." style="width: 95%;"><div id="results"></div>
</div>
<div id="fileContent"><em>Seleccione un archivo</em></div>
<div id="context"><em>Contexto</em></div>
<script>
const tree = document.getElementById('tree');
const fileContent = document.getElementById('fileContent');
const context = document.getElementById('context');
const search = document.getElementById('search');
const results = document.getElementById('results');

function buildTree(base = '', parent = null) {
    fetch(`?action=list&dir=${encodeURIComponent(base)}`)
    .then(r => r.json())
    .then(data => {
        const ul = document.createElement('ul');
        data.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item.name;
            li.className = item.type === 'dir' ? 'folder' : '';
            li.onclick = e => {
                e.stopPropagation();
                if (item.type === 'dir') {
                    if (li.querySelector('ul')) li.removeChild(li.querySelector('ul'));
                    else buildTree(item.path, li);
                } else viewFile(item.path);
            };
            ul.appendChild(li);
        });
        if (parent) parent.appendChild(ul);
        else tree.appendChild(ul);
    });
}

function viewFile(path, highlight = '') {
    fetch(`?action=analyze&file=${encodeURIComponent(path)}`)
    .then(r => r.json())
    .then(data => {
        let lines = data.content.split('\n');
        if (highlight) {
            lines = lines.map((l, i) => l.includes(highlight) ? `<mark>${l}</mark>` : l);
        }
        fileContent.innerHTML = `<h3>${path}</h3><pre>${lines.join('\n')}</pre>`;
        let ctx = `<h3>Contexto</h3>`;
        if (data.classes.length) ctx += `<strong>Clases:</strong><ul>` + data.classes.map(c => `<li>${c}</li>`).join('') + `</ul>`;
        if (data.functions.length) ctx += `<strong>Funciones:</strong><ul>` + data.functions.map(f => `<li>${f}</li>`).join('') + `</ul>`;
        if (data.includes.length) ctx += `<strong>Includes:</strong><ul>` + data.includes.map(i => `<li>${i}</li>`).join('') + `</ul>`;
        if (data.sql_queries.length) ctx += `<strong>SQL:</strong>` + data.sql_queries.map(q => `<code class='sql'>${q}</code>`).join('');
        if (data.referenced_by.length) ctx += `<strong>Llamado por:</strong><ul>` + data.referenced_by.map(f => `<li><a href='#' onclick="viewFile('${f}')">${f}</a></li>`).join('') + `</ul>`;
        if (data.mvc.controller || data.mvc.model) {
            ctx += `<strong>MVC Detectado:</strong><ul>`;
            if (data.mvc.controller) ctx += `<li>Controlador: <a href='#' onclick="viewFile('${data.mvc.controller}')">${data.mvc.controller}</a></li>`;
            if (data.mvc.model) ctx += `<li>Modelo: <a href='#' onclick="viewFile('${data.mvc.model}')">${data.mvc.model}</a></li>`;
            ctx += `</ul>`;
        }
        context.innerHTML = ctx;
    });
}

search.oninput = function() {
    const q = this.value.trim();
    if (!q) return results.innerHTML = '';
    fetch(`?action=search&term=${encodeURIComponent(q)}`)
    .then(r => r.json())
    .then(list => {
        results.innerHTML = '<ul>' + list.map(f => `<li><a href='#' onclick="viewFile('${f}', '${q}')">${f}</a></li>`).join('') + '</ul>';
    });
};

buildTree();
</script>
</body>
</html>