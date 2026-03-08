<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Http;

class AiCategorizationService
{
    public function categorize(string $message): array
    {
        $categories = Category::active()->get(['id', 'name', 'type']);
        $categoriesContext = $categories->map(fn($cat) => "ID: {$cat->id} | Nombre: {$cat->name} | Tipo: {$cat->type}")->implode("\n");

        $prompt = <<<EOT
Eres un asistente financiero minimalista. Tu objetivo es analizar el mensaje del usuario y extraer los datos.

Reglas ESTRICTAS:
1. Tipos permitidos: "expense" (gastos), "income" (ingresos), o "loan" (cuando el usuario presta dinero a alguien).
2. Si es "expense" o "income", usa un "category_id" de la siguiente lista exacta:
{$categoriesContext}
3. Si es un "loan" (préstamo a alguien), el "category_id" debe ser null, pero DEBES llenar el campo "debtor_name" con el nombre de la persona a la que se le prestó.
4. Responde ÚNICAMENTE con un objeto JSON válido.

Formato JSON requerido:
{
    "type": "expense", // o "income", o "loan"
    "category_id": integer o null,
    "amount": float,
    "description": "string breve del concepto",
    "debtor_name": "string o null"
}

Mensaje del usuario: "{$message}"
EOT;

        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'response_format' => ['type' => 'json_object'],
                'messages' => [['role' => 'system', 'content' => $prompt]]
            ]);

        if (!$response->successful()) throw new \Exception('Error al comunicarse con la IA');

        return json_decode($response->json('choices.0.message.content'), true);
    }
}
