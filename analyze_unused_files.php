<?php

$root = '/Users/admin/Documents/GeekBoard';
$excludeDirs = ['.git', 'node_modules', 'vendor', '.gemini', '.vscode', 'fpdf_temp', 'logs'];
$extensions = ['php', 'html', 'js', 'css'];

$files = [];
$references = [];

// 1. Scan Directory
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $path = $file->getPathname();
        $relativePath = str_replace($root . '/', '', $path);
        
        // Check excludes
        $exclude = false;
        foreach ($excludeDirs as $dir) {
            if (strpos($relativePath, $dir . '/') === 0) {
                $exclude = true;
                break;
            }
        }
        if ($exclude) continue;

        $ext = $file->getExtension();
        $files[$relativePath] = [
            'path' => $relativePath,
            'ext' => $ext,
            'size' => $file->getSize(),
            'mtime' => $file->getMTime(),
            'suspicious' => isSuspicious($relativePath)
        ];

        // 2. Parse for references
        if (in_array($ext, $extensions)) {
            $content = file_get_contents($path);
            
            // PHP Includes/Requires
            preg_match_all('/(include|require)(_once)?\s*[\(\'"\s](.+?)[\'"\)]/i', $content, $matches);
            foreach ($matches[3] as $match) {
                $clean = cleanPath($match);
                if ($clean) $references[$clean][] = $relativePath;
            }

            // Links (href, src, action, url)
            preg_match_all('/(href|src|action|url)\s*=\s*["\']([^"\']+)["\']/', $content, $matches);
            foreach ($matches[2] as $match) {
                 $clean = cleanPath($match);
                 if ($clean) $references[$clean][] = $relativePath;
            }
            
            // Fetch / AJAX
            preg_match_all('/(fetch|open)\s*\(\s*["\']([^"\']+)["\']/', $content, $matches);
            foreach ($matches[2] as $match) {
                $clean = cleanPath($match);
                if ($clean) $references[$clean][] = $relativePath;
            }
             // window.location
            preg_match_all('/window\.location\.href\s*=\s*["\']([^"\']+)["\']/', $content, $matches);
            foreach ($matches[1] as $match) {
                $clean = cleanPath($match);
                if ($clean) $references[$clean][] = $relativePath;
            }
        }
    }
}

function isSuspicious($path) {
    $name = basename($path);
    if (preg_match('/(backup|old|copie|copy|test|temp|tmp|draft|\.bak|\.old|\.save|_\d{4,}|^[a-z]\.php$)/i', $name)) {
        return "Naming convention";
    }
    if (preg_match('/(2|3|4|5|fixed|final|working|new|simple|simplified|corrected|restored)\.(php|html|js|css)$/i', $name)) {
         return "Versioned/Duplicate name";
    }
    return false;
}

function cleanPath($path) {
    // Remove query strings
    $path = explode('?', $path)[0];
    // Remove anchors
    $path = explode('#', $path)[0];
    
    // Simple normalization - this is not perfect but covers most cases
    // We want to match filename.ext
    return basename($path);
}

// 3. Analyze Orphans
$orphans = [];
foreach ($files as $path => $info) {
    $basename = basename($path);
    // If not referenced and not an index/common entry point
    $isReferenced = false;
    
    // Check if basename is in references keys
    if (isset($references[$basename])) {
        $isReferenced = true;
    }
    
    // Fallback: check full relative path matches in references
    // (A bit harder with simple cleanPath logic, but trying to capture 'folder/file.php')
    // We'll rely on basename matching for now as it's most common in PHP includes
    
    if (!$isReferenced) {
        $orphans[] = $path;
    }
}

// 4. Output
$output = [
    'total_files' => count($files),
    'suspicious_files' => array_keys(array_filter($files, fn($f) => $f['suspicious'])),
    'potential_orphans' => $orphans
];

echo json_encode($output, JSON_PRETTY_PRINT);

?>
