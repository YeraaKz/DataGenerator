<?php

namespace App\Controller;

use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DataGeneratorController extends AbstractController
{
    private $alphabets = [
        'en_US' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'ru_RU' => 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ',
        'fr_FR' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZàâæçéèêëîïôœùûüÿÀÂÆÇÉÈÊËÎÏÔŒÙÛÜŸ'
    ];

    #[Route('api/generate', name: 'app_data_generator')]
    public function index(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);

        $locale = $content['locale'] ?? 'en_US';
        $page = $content['page'] ?? 1;
        $limit = $content['limit'];
        $userSeed = $content['seed'] ?? 0;

        $compositeSeed = intval($userSeed) + $page;

        $faker = Factory::create($locale);
        $faker->seed($compositeSeed);

        $data = [];
        for ($i = 0; $i < $limit; $i++) {
            $data[] = [
                'number' => ($page - 1) * $limit + $i + 1,
                'uuid' => $faker->uuid,
                'name' => $faker->name,
                'address' => $faker->address,
                'phone' => $faker->phoneNumber
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/add-errors', name: 'app_add_errors')]
    public function addErrors(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        $data = $content['data'] ?? [];
        $errorsPerRecord = $content['errorsPerRecord'] ?? 0;
        $locale = $content['locale'] ?? 'en_US';

        $faker = Factory::create($locale);
        foreach ($data as &$item) {
            $item['name'] = $this->applyErrors($item['name'], $locale, $errorsPerRecord, $faker);
            $item['address'] = $this->applyErrors($item['address'], $locale, $errorsPerRecord, $faker);
            $item['phone'] = $this->applyErrors($item['phone'], $locale, $errorsPerRecord, $faker);
        }

        return $this->json($data);
    }

    private function applyErrors($text, $locale, $errorsPerRecord,  Generator $faker)
    {
        if (mb_strlen($text) < $errorsPerRecord) {
            $errorsPerRecord = mb_strlen($text);
        }

        for ($j = 0; $j < floor($errorsPerRecord); $j++) {
            $text = $this->getErrorType($text, $locale, $faker);
        }

        $fractionalPart = $errorsPerRecord - floor($errorsPerRecord);
        if ($faker->boolean($fractionalPart * 100)) {
            $text = $this->getErrorType($text, $locale, $faker);
        }
        return $text;
    }

    private function getErrorType($text, $locale, Generator $faker)
    {
        $type = $faker->randomElement(['delete', 'add', 'swap']);
        switch ($type) {
            case 'delete':
                $pos = $faker->numberBetween(0, mb_strlen($text) - 1);
                $text = mb_substr($text, 0, $pos) . mb_substr($text, $pos + 1);
                break;
            case 'add':
                $pos = $faker->numberBetween(0, mb_strlen($text));
                $char = $this->getRandomLetter($locale); // адаптировать под локаль если нужно
                $text = mb_substr($text, 0, $pos) . $char . mb_substr($text, $pos);
                break;
            case 'swap':
                if (mb_strlen($text) > 1) {
                    $pos = $faker->numberBetween(0, mb_strlen($text) - 1);
                    $temp1 = mb_substr($text, $pos, 1);
                    $temp2 = mb_substr($text, $pos + 1, 1);
                    $text = mb_substr($text, 0, $pos) . $temp2 . $temp1 . mb_substr($text, $pos + 2);
                }
                break;
        }
        return $text;
    }

    private function getRandomLetter($locale)
    {
        $alphabet = $this->alphabets[$locale];
        $index = mt_rand(0, mb_strlen($alphabet) - 1);
        return mb_substr($alphabet, $index, 1);

    }
}
