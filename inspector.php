<?php
// inspector.php
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
        $result = [
            'content' => htmlspecialchars($content),
            'includes' => [],
            'sql_queries' => [],
            'referenced_by' => [],
        ];

        // Detect includes
        preg_match_all('/(?:include|require)(_once)?\s*\(?[\'"](.+?)[\'"]\)?\s*;/', $content, $matches);
        $result['includes'] = $matches[2];

        // Detect SQL queries
        preg_match_all('/(SELECT|INSERT|UPDATE|DELETE)\s.+?;/is', $content, $queries);
        $result['sql_queries'] = array_map('trim', $queries[0]);

        // Reverse search: who includes this?
        $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
        foreach ($allFiles as $file) {
            if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $scan = file_get_contents($file);
                if (strpos($scan, $filePath) !== false && $filePath !== basename($file)) {
                    $result['referenced_by'][] = str_replace(__DIR__ . '/', '', $file);
                }
            }
        }

        return $result;
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
    <title>Inspector de Proyecto PHP</title>
    <style>
        body { font-family: sans-serif; display: flex; height: 100vh; margin: 0; }
        #tree, #fileContent, #context { padding: 10px; overflow: auto; }
        #tree { width: 25%; border-right: 1px solid #ccc; background: #f9f9f9; }
        #fileContent { width: 40%; border-right: 1px solid #ccc; background: #fff; white-space: pre-wrap; font-family: monospace; }
        #context { width: 35%; background: #f3f3f3; }
        li { cursor: pointer; }
        .folder { font-weight: bold; }
        ul { list-style: none; padding-left: 20px; }
        h3 { margin-top: 10px; margin-bottom: 5px; }
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
                            li.removeChild(li.querySelector('ul')); // toggle off
                        } else {
                            buildTree(item.path, li); // load subdir
                        }
                    } else {
                        viewFile(item.path);
                    }
                };
                ul.appendChild(li);
            });

            if (parentElement) {
                parentElement.appendChild(ul); // append to folder <li>
            } else {
                tree.innerHTML = ''; // root
                tree.appendChild(ul);
            }
        });
}

        function viewFile(filePath) {
            fetch(`?action=analyze&file=${filePath}`)
                .then(res => res.json())
                .then(data => {
                    fileContent.innerHTML = `<h2>${filePath}</h2><pre>${data.content}</pre>`;
                    contextPanel.innerHTML = `<h2>Contexto</h2>`;

                    contextPanel.innerHTML += `<h3>Includes</h3><ul>${data.includes.map(i => `<li>${i}</li>`).join('') || '<li>Ninguno</li>'}</ul>`;
                    contextPanel.innerHTML += `<h3>SQL</h3><ul>${data.sql_queries.map(q => `<li><code>${q}</code></li>`).join('') || '<li>No detectadas</li>'}</ul>`;
                    contextPanel.innerHTML += `<h3>Llamado por</h3><ul>${data.referenced_by.map(f => `<li>${f}</li>`).join('') || '<li>Nadie</li>'}</ul>`;
                });
        }

        buildTree();
    </script>
</body>
</html>
