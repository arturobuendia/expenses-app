<?php 

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Http;

class AiCategorizationService
{
    public function categorize(string $message): array
    {
        // 1. Obtener el catálogo estricto de la base de datos
        $categories = Category::active()->get(['id', 'name', 'type']);
        
        $categoriesContext = $categories->map(function ($cat) {
            return "ID: {$cat->id} | Nombre: {$cat->name} | Tipo: {$cat->type}";
        })->implode("\n");

        // 2. Armar el prompt estricto (KISS)
        $prompt = <<<EOT
Eres un asistente financiero minimalista. Tu objetivo es analizar el mensaje del usuario y extraer los datos de su transacción.

Reglas ESTRICTAS:
1. Solo puedes usar un "category_id" de la siguiente lista exacta:
{$categoriesContext}
2. Si es un gasto, el "type" debe ser "expense". Si es entrada de dinero, "income".
3. Responde ÚNICAMENTE con un objeto JSON válido, sin markdown ni explicaciones adicionales.

Formato JSON requerido:
{
    "type": "expense o income",
    "category_id": integer,
    "amount": float,
    "description": "string breve del concepto"
}

Mensaje del usuario: "{$message}"
EOT;

        // 3. Llamar a la API de OpenAI (o el LLM que prefieras)
        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini', // Rápido y barato
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $prompt]
                ]
            ]);

        if (!$response->successful()) {
            throw new \Exception('Error al comunicarse con la IA');
        }

        $jsonResult = $response->json('choices.0.message.content');
        
        return json_decode($jsonResult, true);
    }
}