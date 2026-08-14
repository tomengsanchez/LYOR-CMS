<?php
/**
 * One-off codemod: standardize API controller error responses.
 * Run from project root: php cli/refactor_api_errors.php
 */
$root = dirname(__DIR__);
$files = glob($root . '/App/Controllers/Api/*.php') ?: [];
$skip = ['AuthController.php', 'MetaController.php'];

$replacements = [
    [
        "            http_response_code(403);\n            \$this->json(['error' => 'Forbidden']);\n            return;",
        "            \$this->apiForbidden();\n            return;",
    ],
    [
        "            http_response_code(403);\n            \$this->json(['error' => 'Forbidden']);\n        }",
        "            \$this->apiForbidden();\n            return;\n        }",
    ],
    [
        "            http_response_code(403);\n            \$this->json(['error' => 'Forbidden', 'message' => 'Project not allowed for current user']);",
        "            \$this->apiForbiddenProject();",
    ],
    [
        "                http_response_code(403);\n                \$this->json(['error' => 'Forbidden', 'message' => 'Project not allowed for current user']);",
        "                \$this->apiForbiddenProject();",
    ],
    [
        "            http_response_code(403);\n            \$this->json(['error' => 'Forbidden', 'message' => 'Not allowed']);",
        "            \$this->apiForbidden('Not allowed.');",
    ],
    [
        "            http_response_code(404);\n            \$this->json(['error' => 'Not found']);",
        "            \$this->apiNotFound();\n            return;",
    ],
    [
        "            http_response_code(404);\n            \$this->json(['error' => 'Not found']);",
        "            \$this->apiNotFound();\n            return;",
    ],
    [
        "            http_response_code(400);\n            \$this->json(['error' => 'Bad Request', 'message' => ",
        "            \$this->apiBadRequest(",
    ],
    [
        "            http_response_code(400);\n            \$this->json(['error' => 'ValidationError', 'message' => ",
        "            \$this->apiValidationError(",
    ],
    [
        "            http_response_code(403);\n            \$this->json(['error' => 'Forbidden', 'message' => 'Admin only']);",
        "            \$this->apiForbidden('Administrator access required.');",
    ],
    [
        "            http_response_code(403);\n            \$this->json(['error' => 'Forbidden', 'message' => 'Administrator access required.']);",
        "            \$this->apiForbidden('Administrator access required.');",
    ],
];

foreach ($files as $path) {
    $base = basename($path);
    if (in_array($base, $skip, true)) {
        continue;
    }
    $content = file_get_contents($path);
    $content = str_replace("\r\n", "\n", $content);
    $original = $content;
    foreach ($replacements as [$from, $to]) {
        $content = str_replace($from, $to, $content);
    }
    // Fix calls left as apiBadRequest('msg']);
    $content = preg_replace(
        "/\\\$this->api(BadRequest|ValidationError)\\(([^\\]]+)\\]\\);/",
        '$this->api$1($2);',
        $content
    );
    // Close apiBadRequest/apiValidationError calls that lost ]);
    $content = preg_replace(
        '/\$this->api(BadRequest|ValidationError)\(([^;]+)\);\s*\n\s*return;/',
        '$this->api$1($2);' . "\n            return;",
        $content
    );
    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "Updated: {$base}\n";
    }
}

// Second pass: json-only lines (when http_response_code was already removed or absent)
$jsonOnly = [
    ["\$this->json(['error' => 'Forbidden']);", "\$this->apiForbidden();\n            return;"],
    ["\$this->json(['error' => 'Forbidden', 'message' => 'Project not allowed for current user']);", "\$this->apiForbiddenProject();\n            return;"],
    ["\$this->json(['error' => 'Forbidden', 'message' => 'Not allowed']);", "\$this->apiForbidden('Not allowed.');\n            return;"],
    ["\$this->json(['error' => 'Not found']);", "\$this->apiNotFound();\n            return;"],
    ["\$this->json(['error' => 'Forbidden', 'message' => 'Admin only']);", "\$this->apiForbidden('Administrator access required.');\n            return;"],
];
foreach ($files as $path) {
    $base = basename($path);
    if (in_array($base, $skip, true)) {
        continue;
    }
    $content = str_replace("\r\n", "\n", file_get_contents($path));
    $original = $content;
    foreach ($jsonOnly as [$from, $to]) {
        $content = str_replace($from, $to, $content);
    }
    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "Pass2: {$base}\n";
    }
}

echo "Done.\n";
