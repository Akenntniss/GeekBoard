<?php
$f = '/var/www/mdgeek.top/api/v2/knowledge/list.php';
if (!file_exists($f)) {
    die("File not found: $f\n");
}
$c = file_get_contents($f);
$old = "strip_tags(\$article['content'] ?? '');";
// Using single quotes for regex to avoid confusion, and properly escaping backslashes for PHP string
// Regex: /<style\b[^>]*>(.*?)<\/style>/is
// In PHP string: '/<style\\b[^>]*>(.*?)<\\/style>/is'
$new = "strip_tags(preg_replace('/<style\\\\b[^>]*>(.*?)<\\\\/style>/is', '', \$article['content'] ?? ''));";

if (strpos($c, $old) === false) {
    die("Search string not found in file.\n");
}

$c = str_replace($old, $new, $c);
file_put_contents($f, $c);
echo "Successfully patched $f\n";
?>