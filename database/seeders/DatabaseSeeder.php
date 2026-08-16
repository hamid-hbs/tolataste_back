<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Settings;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Comptes de démo (hash bcrypt automatique via cast 'hashed')
        User::create(['name' => 'Admin Tola', 'email' => 'admin@tolataste.com', 'password' => 'password123', 'role' => 'admin']);
        User::create(['name' => 'Gérant test', 'email' => 'manager@tolataste.com', 'password' => 'password123', 'role' => 'manager']);
        User::create(['name' => 'Serveur test', 'email' => 'serveur@tolataste.com', 'password' => 'password123', 'role' => 'serveur']);
        User::create(['name' => 'Chef test', 'email' => 'cuisine@tolataste.com', 'password' => 'password123', 'role' => 'cuisine']);

        // 10 tables réparties Salle / Terrasse / VIP
        foreach ([
            [1, 'Salle'], [2, 'Salle'], [3, 'Salle'], [4, 'Salle'],
            [5, 'Terrasse'], [6, 'Terrasse'], [7, 'Terrasse'],
            [8, 'VIP'], [9, 'VIP'], [10, 'Salle'],
        ] as [$num, $zone]) {
            Table::create(['number' => $num, 'zone' => $zone]);
        }

        $this->seedMenu();
    }

    private function seedMenu(): void
    {
        $menu = [
            'Omelettes' => [
                ['name' => 'Omelette nature', 'description' => 'Omelette aux œufs frais, servie chaude.', 'price' => 1000],
                ['name' => 'Omelette au jambon', 'description' => 'Omelette garnie de jambon fondant.', 'price' => 1500],
                ['name' => 'Omelette au fromage', 'description' => 'Omelette au fromage fondant.', 'price' => 1500],
                ['name' => 'Omelette paysanne', 'description' => 'Omelette aux légumes frais du marché.', 'price' => 2000],
            ],
            'Chocolats' => [
                ['name' => 'Chocolat noir intense', 'description' => 'Tablette de chocolat noir 70%.', 'price' => 2500],
                ['name' => 'Chocolat au lait fondant', 'description' => 'Chocolat au lait doux et crémeux.', 'price' => 2200],
                ['name' => 'Chocolat chaud traditionnel', 'description' => 'Tasse de chocolat chaud onctueux.', 'price' => 1800],
            ],
            'Thés & Infusions' => [
                ['name' => 'Thé vert menthe', 'description' => 'Thé vert infusé à la menthe fraîche.', 'price' => 1200],
                ['name' => 'Thé noir Earl Grey', 'description' => 'Thé noir parfumé à la bergamote.', 'price' => 1200],
                ['name' => 'Infusion bissap', 'description' => 'Infusion d\'hibiscus rafraîchissante.', 'price' => 1000],
            ],
            'Plats authentiques' => [
                ['name' => 'Poulet braisé + riz', 'description' => 'Poulet braisé servi avec riz parfumé.', 'price' => 6000],
                ['name' => 'Poisson braisé + alloco', 'description' => 'Poisson braisé accompagné de bananes plantain.', 'price' => 7000],
                ['name' => 'Atassi', 'description' => 'Sauce aux haricots servie avec du riz.', 'price' => 4500],
                ['name' => 'Pente douce', 'description' => 'Spécialité locale aux légumes et viande.', 'price' => 5000],
                ['name' => 'Poulet dg', 'description' => 'Poulet sauté aux légumes et bananes.', 'price' => 6500],
            ],
            'Accompagnements' => [
                ['name' => 'Aloco', 'description' => 'Bananes plantain frites.', 'price' => 1500],
                ['name' => 'Frites maison', 'description' => 'Pommes de terre frites croustillantes.', 'price' => 1500],
                ['name' => 'Riz blanc', 'description' => 'Riz blanc cuit à la vapeur.', 'price' => 1000],
                ['name' => 'Alloco + sauce piment', 'description' => 'Aloco accompagné de sauce pimentée.', 'price' => 2000],
            ],
            'Jus nature' => [
                ['name' => 'Jus de bissap', 'description' => 'Jus d\'hibiscus glacé, 50cl.', 'price' => 1500],
                ['name' => 'Jus de gingembre', 'description' => 'Jus de gingembre pétillant, 50cl.', 'price' => 1500],
                ['name' => 'Jus de baobab', 'description' => 'Jus de baobab onctueux, 50cl.', 'price' => 1800],
                ['name' => 'Jus de mangue', 'description' => 'Jus de mangue douce, 50cl.', 'price' => 2000],
            ],
        ];

        foreach ($menu as $categoryLabel => $products) {
            $category = Category::create(['label' => $categoryLabel]);

            foreach ($products as $product) {
                Product::create([
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'category_id' => $category->id,
                    'active' => true,
                ]);
            }
        }
    }
}
