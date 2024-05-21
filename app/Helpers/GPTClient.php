<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Orhanerday\OpenAi\OpenAi;

class GPTClient implements Arrayable, Jsonable
{

    protected $open_ai;
    public $response;
    public function __construct($prompt)
    {

        $this->open_ai = new OpenAi(config('services.open-ai.key'));
        $this->open_ai->setHeader(["Connection" => "keep-alive"]);
        $this->chat('gpt-4', $prompt);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function chat($model, $prompt)
    {
     
        $chat = $this->open_ai->chat([
            'model' => $model,
            'messages' => [
                [
                    "role" => "system",
                    "content" => "You are a helpful assistant."
                ],
                [
                    "role" => "user",
                    "content" => $prompt
                ],
            ],
            'temperature' => 1.0,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ]);

        $d = json_decode($chat);
         Log::info([$d]);
        $this->response = trim($d->choices[0]->message->content);
    }
}
