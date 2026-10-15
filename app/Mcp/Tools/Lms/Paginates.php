<?php

namespace App\Mcp\Tools\Lms;

use Illuminate\Contracts\JsonSchema\JsonSchema;

trait Paginates
{
    /**
     * @return array<string, JsonSchema>
     */
    protected function pageArguments(JsonSchema $schema): array
    {
        return [
            'pagina' => $schema->integer()
                ->description('Qué página querés. La 1 trae los más recientes. Si no la indicás, es la 1.'),
            'cantidad' => $schema->integer()
                ->description('Cuántos resultados por página. Si no lo indicás, son 15. El máximo es 100.'),
        ];
    }
}
