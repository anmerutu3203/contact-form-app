<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create('ja_JP');

        $categoryIds = Category::pluck('id')->all();
        $tagIds = Tag::pluck('id')->all();

        for ($i = 0; $i < 20; $i++) {
            $contact = Contact::create([
                'category_id' => $faker->randomElement($categoryIds),
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'gender' => $faker->numberBetween(1, 3),
                'email' => $faker->unique()->safeEmail(),
                // ハイフンなし10〜11桁の数字にする
                'tel' => $faker->numerify(
                    $faker->boolean() ? '###########' : '##########'
                ),
                'address' => $faker->address(),
                'building' => $faker->boolean(70) ? $faker->secondaryAddress() : null,
                'detail' => mb_substr($faker->realText(100), 0, 120),
            ]);

            // 既存タグからランダムに1〜3件attach
            $attachCount = $faker->numberBetween(1, 3);
            $randomTagIds = $faker->randomElements($tagIds, min($attachCount, count($tagIds)));
            $contact->tags()->attach($randomTagIds);
        }
    }
}