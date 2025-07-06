<?php
// inspector.php - herramienta de inspección PHP
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $base = realpath(__DIR__);

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
        $highlighted = htmlspecialchars($content);

        // Detect classes and functions
        preg_match_all('/\bclass\s+(\w+)/', $content, $classes);
        preg_match_all('/\bfunction\s+(\w+)/', $content, $functions);

        // Detect includes
        preg_match_all('/(?:include|require)(_once)?\s*\(?[\'"](.+?)[\'"]\)?\s*;/', $content, $matches);
        $includes = $matches[2];

        // Detect SQL queries
        preg_match_all('/(SELECT|INSERT|UPDATE|DELETE)\s.+?;/is', $content, $queries);
        $sql = array_map('trim', $queries[0]);

        // Detect who references this file
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

        return [
            'content' => htmlspecialchars($content),
            'classes' => $classes[1],
            'functions' => $functions[1],
            'includes' => $includes,
            'sql_queries' => $sql,
            'referenced_by' => $refers
        ];
    }

    if ($_GET['action'] === 'list') {
        $dir = isset($_GET['dir']) ? realpath(__DIR__ . '/' . $_GET['dir']) : __DIR__;
        if (strpos($dir, __DIR__) !== 0) {
            echo json_encode(['error' => 'Invalid path']);
        } else {
            echo json_encode(listDir($dir));
        }
    } elseif ($_GET['action'] === 'analyze' && isset($_GET['file'])) {
        echo json_encode(analyzeFile($_GET['file']));
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inspector PHP - Lógica de Negocio</title>
    <style>
        body { margin: 0; font-family: sans-serif; display: flex; height: 100vh; }
        #tree, #fileContent, #context { padding: 10px; overflow: auto; }
        #tree { width: 25%; background: #f5f5f5; border-right: 1px solid #ccc; }
        #fileContent { width: 45%; border-right: 1px solid #ccc; background: #fff; font-family: monospace; white-space: pre-wrap; }
        #context { width: 30%; background: #f9f9f9; }
        ul { list-style: none; padding-left: 20px; }
        li { cursor: pointer; }
        .folder { font-weight: bold; }
        h3 { margin-top: 10px; margin-bottom: 5px; }

        /* Syntax highlighting */
        .php { color: #000080; font-weight: bold; }
        .keyword { color: darkred; font-weight: bold; }
        .string { color: green; }
        .comment { color: grey; font-style: italic; }
        .sql { background: #eef; padding: 4px; display: block; margin-bottom: 5px; border-left: 3px solid #88f; }
    </style>
</head>
<body>
    <div id="tree"><strong>Cargando...</strong></div>
    <div id="fileContent"><em>Seleccione un archivo...</em></div>
    <div id="context"><em>Contexto del archivo...</em></div>

    <script>
        const tree = document.getElementById('tree');
        const fileContent = document.getElementById('fileContent');
        const contextPanel = document.getElementById('context');

        function buildTree(base = '', parentElement = null) {
            return fetch(`?action=list&dir=${encodeURIComponent(base)}`)
                .then(res => res.json())
                .then(data => {
                    const ul = document.createElement('ul');
                    data.forEach(item => {
                        const li = document.createElement('li');
                        li.textContent = item.name;
                        li.dataset.path = item.path;
                        li.className = item.type === 'dir' ? 'folder' : '';
                        li.onclick = function(e) {
                            e.stopPropagation();
                            if (item.type === 'dir') {
                                if (li.querySelector('ul')) {
                                    li.removeChild(li.querySelector('ul'));
                                } else {
                                    buildTree(item.path, li);
                                }
                            } else {
                                viewFile(item.path);
                            }
                        };
                        ul.appendChild(li);
                    });

                    if (parentElement) {
                        parentElement.appendChild(ul);
                    } else {
                        tree.innerHTML = '';
                        tree.appendChild(ul);
                    }
                });
        }

        function highlightSyntax(code) {
            return code
                .replace(/(&lt;\?php|&lt;\?)/g, '<span class="php">$1</span>')
                .replace(/\b(function|class|if|else|elseif|return|foreach|while|try|catch|throw|new)\b/g, '<span class="keyword">$1</span>')
                .replace(/\/\/.*/g, '<span class="comment">$&</span>')
                .replace(/(&quot;.*?&quot;|'.*?')/g, '<span class="string">$1</span>');
        }

        function viewFile(filePath) {
            fetch(`?action=analyze&file=${encodeURIComponent(filePath)}`)
                .then(res => res.json())
                .then(data => {
                    const highlighted = highlightSyntax(data.content);
                    fileContent.innerHTML = `<h2>${filePath}</h2><pre>${highlighted}</pre>`;

                    let ctx = `<h2>Contexto</h2>`;

                    ctx += `<h3>Clases</h3><ul>${(data.classes.length ? data.classes.map(c => `<li>${c}</li>`).join('') : '<li>Ninguna</li>')}</ul>`;
                    ctx += `<h3>Funciones</h3><ul>${(data.functions.length ? data.functions.map(f => `<li>${f}()</li>`).join('') : '<li>Ninguna</li>')}</ul>`;
                    ctx += `<h3>Includes</h3><ul>${(data.includes.length ? data.includes.map(i => `<li>${i}</li>`).join('') : '<li>Ninguno</li>')}</ul>`;

                    ctx += `<h3>SQL</h3>`;
                    if (data.sql_queries.length) {
                        data.sql_queries.forEach(q => ctx += `<code class="sql">${q}</code>`);
                    } else {
                        ctx += `<p>No detectadas</p>`;
                    }

                    ctx += `<h3>Llamado por</h3><ul>`;
                    if (data.referenced_by.length) {
                        data.referenced_by.forEach(f => {
                            ctx += `<li><a href="#" onclick="viewFile('${f}'); return false;">${f}</a></li>`;
                        });
                    } else {
                        ctx += `<li>Nadie</li>`;
                    }
                    ctx += `</ul>`;

                    contextPanel.innerHTML = ctx;
                });
        }

        buildTree();
    </script>
</body>
</html>
