
<?php

function topological_sort($graph)
{
    $result = [];
    $visited = [];
    foreach ($graph as $node => $edges) {
        topological_sort_visit($node, $graph, $visited, $result);
    }
    return array_reverse($result);
}

function topological_sort_visit($node, $graph, &$visited, &$result): void
{
    if (!isset($visited[$node])) {
        $visited[$node] = true;
        foreach ($graph[$node] as $edge) {
            topological_sort_visit($edge, $graph, $visited, $result);
        }
        $result[] = $node;
    }
}

$classToFileMap = require_once '../../vendor/composer/autoload_classmap.php';
$classToFileMap = array_filter($classToFileMap, function($path) {
    return strpos($path, getcwd()) !== false;
});
$directory = new RecursiveDirectoryIterator('./');
$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$dependencies = [];

foreach ($regex as $file) {
    if (strpos($file[0], './resources') === 0) {
        continue;
    }
    if (strpos($file[0], './routes') === 0) {
        continue;
    }
    if($file[0] === './makeAutoload.php'){        
        continue;
    }
    if($file[0] === './autoload_static.php'){
        continue;
    }
    $fileContent = file_get_contents($file[0]);
    // namespace の解析
    preg_match('/\bnamespace\s+([^;]+);/', $fileContent, $namespaceMatch);
    $namespace = $namespaceMatch[1] ?? '';
    // use, extends, trait の use ステートメントの解析
    preg_match_all('/\buse\s+([^;]+);/', $fileContent, $matches);
    preg_match_all('/\bextends\s+([^\s;]+)/', $fileContent, $extendsMatches);
    preg_match_all('/\buse\s+([^;]+)(?=;)/', $fileContent, $traitMatches);
    $allMatches = array_merge($matches[1], $extendsMatches[1], $traitMatches[1]);
    if (empty($namespace) && empty($allMatches)) {
        $dependencies[$file[0]] = [];
    } else {
        $dependencies[$file[0]] = $allMatches;  // 元の依存関係を保持
        foreach ($allMatches as $class) {
            // 名前空間が指定されていない場合、ファイルの名前空間を付与する
            if ($namespace && strpos($class, "\\") !== 0) {
                $dependencies[$file[0]][] = $namespace . "\\" . $class;  // 名前空間を付与した依存関係を追加
            }
        }
    }
}

function resolveDependencies($file, $dependencies, &$resolved, &$seen): void 
{
    $seen[$file] = true;
    foreach ($dependencies[$file] as $dependency) {
        if (!isset($resolved[$dependency]) && isset($dependencies[$dependency])) {
            resolveDependencies($dependency, $dependencies, $resolved, $seen);
        }
    }
    $resolved[$file] = true;
}

$resolved = [];
$seen = [];

foreach (array_keys($dependencies) as $file) {
    if (!isset($seen[$file])) {
        resolveDependencies($file, $dependencies, $resolved, $seen);
    }
}

$newDependencies = [];
foreach ($dependencies as $relativeFile  => $classes) {
    $file = realpath($relativeFile);
    //$class = array_search($file, $classToFileMap);
    //if ($class !== false) {
        $newDependencies[$file] = [];
        foreach ($classes as $depClass) {
            if (array_key_exists($depClass, $classToFileMap)) {
                $newDependencies[$file][] = $classToFileMap[$depClass];
            }
        }
    //}
}
$baseDir = getcwd();
$currentDirName = basename(getcwd());  // 現在のディレクトリ名を取得
$autoloadFileContent = "<?php \r\n";
foreach(array_reverse(topological_sort($newDependencies)) as $file){
    $relativePath = $currentDirName . '/' . str_replace($baseDir, '', $file);  // ディレクトリ名を付与
    $relativePath = str_replace('//', '/', $relativePath);
    $autoloadFileContent .= "require_once '$relativePath';\r\n";
}

file_put_contents('autoload_static.php', $autoloadFileContent);
