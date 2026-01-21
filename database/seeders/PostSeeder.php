<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // Memastikan ada data yang diperlukan
        $users = User::all();
        $categories = Category::all();
        $tags = Tag::all();

        // Membuat 50 post dengan data palsu
        for ($i = 0; $i < 1000; $i++) {
            $title = $faker->sentence();
            $status = $faker->randomElement(['draft', 'published', 'scheduled']);
            $publishedAt = $status === 'published' ? $faker->dateTimeBetween('-1 year', 'now') :
                ($status === 'scheduled' ? $faker->dateTimeBetween('now', '+1 year') : null);

            $post = Post::create([
                'user_id' => $users->random()->id,
                'category_id' => $categories->random()->id,
                'title' => $title,
                'slug' => Str::slug($title),
                'excerpt' => $faker->paragraph(),
                'content' => $faker->paragraphs(rand(3, 7), true),
                'status' => $status,
                'published_at' => $publishedAt,
            ]);

            // Menambahkan 1-5 tag secara acak ke post
            $post->tags()->attach(
                $tags->random(rand(1, 5))->pluck('id')->toArray()
            );
        }
    }
}
