<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\HomePageSection;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\DefaultAdminUser;
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
        $admin = User::updateOrCreate(
            ['email' => 'admin@camping-vibes.test'],
            [
                'name' => 'Administrateur Camping Vibes',
                'password' => 'password',
                'is_admin' => true,
            ],
        );

        DefaultAdminUser::ensureExists();

        $customer = User::updateOrCreate(
            ['email' => 'client@camping-vibes.test'],
            [
                'name' => 'Camille Martin',
                'password' => 'password',
                'is_admin' => false,
            ],
        );

        $categories = collect([
            ['name' => 'Tentes', 'slug' => 'tentes', 'description' => 'Abris rapides, résistants et confortables.'],
            ['name' => 'Sommeil', 'slug' => 'sommeil', 'description' => 'Sacs, matelas et accessoires pour des nuits sereines.'],
            ['name' => 'Cuisine', 'slug' => 'cuisine', 'description' => 'Tout pour cuisiner proprement au camp.'],
            ['name' => 'Accessoires', 'slug' => 'accessoires', 'description' => 'Les détails utiles qui changent un week-end dehors.'],
        ])->mapWithKeys(fn (array $category): array => [
            $category['slug'] => Category::updateOrCreate(['slug' => $category['slug']], $category + ['is_active' => true]),
        ]);

        $products = collect([
            [
                'category_id' => $categories['tentes']->id,
                'name' => 'Tente Aurora 2 places',
                'slug' => 'tente-aurora-2-places',
                'description' => 'Une tente compacte et premium pour deux personnes, pensée pour les départs rapides. Double toit imperméable, ventilation généreuse et montage intuitif pour arriver au camp sans perdre la lumière.',
                'photos' => [
                    'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?auto=format&fit=crop&w=1200&q=80',
                ],
                'price' => 799.000,
                'stock_quantity' => 14,
                'is_featured' => true,
            ],
            [
                'category_id' => $categories['sommeil']->id,
                'name' => 'Sac de couchage Nord 0°C',
                'slug' => 'sac-de-couchage-nord-0c',
                'description' => 'Chaud, léger et agréable au toucher, ce sac de couchage garde une excellente isolation pendant les nuits fraîches. Sa coupe momie reste confortable sans gaspiller de volume dans le sac.',
                'photos' => [
                    'https://images.unsplash.com/photo-1523987355523-c7b5b0dd90a7?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=1200&q=80',
                ],
                'price' => 429.000,
                'stock_quantity' => 22,
                'is_featured' => true,
            ],
            [
                'category_id' => $categories['cuisine']->id,
                'name' => 'Réchaud Ember Pro',
                'slug' => 'rechaud-ember-pro',
                'description' => 'Un réchaud stable, précis et compact pour cuisiner au lever du jour comme après une longue marche. Allumage rapide, support large et réglage fin de la flamme.',
                'photos' => [
                    'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1532339142463-fd0a8979791a?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80',
                ],
                'price' => 229.000,
                'stock_quantity' => 31,
                'is_featured' => true,
            ],
            [
                'category_id' => $categories['accessoires']->id,
                'name' => 'Lampe solaire Halo',
                'slug' => 'lampe-solaire-halo',
                'description' => 'Une lampe solaire minimaliste avec trois intensités, parfaite pour la table, la tente ou une lecture tardive. Recharge USB-C et accroche intégrée.',
                'photos' => [
                    'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1487730116645-74489c95b41b?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1478827536114-da961b7f86d2?auto=format&fit=crop&w=1200&q=80',
                ],
                'price' => 119.000,
                'stock_quantity' => 46,
                'is_featured' => true,
            ],
            [
                'category_id' => $categories['sommeil']->id,
                'name' => 'Matelas Summit Air',
                'slug' => 'matelas-summit-air',
                'description' => 'Matelas gonflable isolant avec surface douce et valve rapide. Il offre un vrai confort de nuit tout en gardant un format compact dans le sac.',
                'photos' => [
                    'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1445307806294-bff7f67ff225?auto=format&fit=crop&w=1200&q=80',
                ],
                'price' => 349.000,
                'stock_quantity' => 18,
                'is_featured' => false,
            ],
            [
                'category_id' => $categories['cuisine']->id,
                'name' => 'Set cuisine Nomad',
                'slug' => 'set-cuisine-nomad',
                'description' => 'Popote légère en aluminium anodisé, deux bols, deux tasses et une housse propre. Le set s’empile parfaitement pour gagner de la place.',
                'photos' => [
                    'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1532339142463-fd0a8979791a?auto=format&fit=crop&w=1200&q=80',
                ],
                'price' => 189.000,
                'stock_quantity' => 27,
                'is_featured' => false,
            ],
        ])->mapWithKeys(fn (array $product): array => [
            $product['slug'] => Product::updateOrCreate(['slug' => $product['slug']], $product + ['is_active' => true]),
        ]);

        collect([
            [
                'key' => 'hero',
                'title' => 'Camping Vibes',
                'subtitle' => 'La boutique premium pour préparer un campement beau, simple et fiable.',
                'body' => null,
                'image' => 'https://images.unsplash.com/photo-1478827536114-da961b7f86d2?auto=format&fit=crop&w=1800&q=80',
                'primary_label' => 'Explorer la boutique',
                'primary_url' => '/produits',
                'secondary_label' => 'Lire le journal',
                'secondary_url' => '/blog',
                'sort_order' => 1,
            ],
            [
                'key' => 'story',
                'title' => 'Moins de matériel moyen, plus de moments dehors.',
                'subtitle' => 'Notre histoire',
                'body' => 'Nous sélectionnons des produits faciles à comprendre, agréables à utiliser et suffisamment robustes pour suivre vos week-ends toute l’année.',
                'image' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=80',
                'primary_label' => 'Voir la sélection',
                'primary_url' => '/produits',
                'sort_order' => 2,
            ],
            [
                'key' => 'promo',
                'title' => 'Prêt pour une démo de commande complète.',
                'subtitle' => 'Prototype',
                'body' => 'Ajoutez un produit, connectez-vous et confirmez le paiement simulé. Filament reçoit immédiatement la commande.',
                'image' => 'https://images.unsplash.com/photo-1532339142463-fd0a8979791a?auto=format&fit=crop&w=1200&q=80',
                'primary_label' => 'Tester le checkout',
                'primary_url' => '/produits',
                'sort_order' => 3,
            ],
        ])->each(fn (array $section): HomePageSection => HomePageSection::updateOrCreate(['key' => $section['key']], $section + ['is_active' => true]));

        collect([
            [
                'title' => 'Composer un campement confortable en moins de quinze minutes',
                'slug' => 'composer-un-campement-confortable',
                'featured_image' => 'https://images.unsplash.com/photo-1487730116645-74489c95b41b?auto=format&fit=crop&w=1200&q=80',
                'excerpt' => 'Une méthode simple pour installer les zones sommeil, cuisine et lumière sans perdre de temps.',
                'content' => '<p>Un bon campement commence par une organisation claire. Placez la tente sur un sol plat, gardez la cuisine à distance du couchage et préparez la lumière avant la tombée de la nuit.</p><h2>Prioriser les gestes utiles</h2><p>Dépliez uniquement ce qui sert tout de suite. Les accessoires restent groupés dans une pochette afin de préserver un espace calme et facile à ranger.</p>',
                'published_at' => now()->subDays(6),
            ],
            [
                'title' => 'Checklist pour un week-end outdoor réussi',
                'slug' => 'checklist-week-end-outdoor-reussi',
                'featured_image' => 'https://images.unsplash.com/photo-1445307806294-bff7f67ff225?auto=format&fit=crop&w=1200&q=80',
                'excerpt' => 'Les indispensables à vérifier avant de partir, du couchage à la cuisine.',
                'content' => '<p>Avant de partir, vérifiez le trio abri, chaleur et eau. Ajoutez ensuite les éléments de confort qui rendent le campement agréable : lampe, assise, table compacte et trousse de réparation.</p><h2>Le bon réflexe</h2><p>Testez chaque nouvel équipement chez vous une fois. Vous gagnerez du temps et éviterez les surprises au camp.</p>',
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Pourquoi choisir moins de produits mais de meilleure qualité',
                'slug' => 'moins-de-produits-meilleure-qualite',
                'featured_image' => 'https://images.unsplash.com/photo-1471115853179-bb1d604434e0?auto=format&fit=crop&w=1200&q=80',
                'excerpt' => 'Un sac plus léger, un montage plus fluide et du matériel qui dure plus longtemps.',
                'content' => '<p>La qualité se voit surtout quand les conditions changent. Un zip fiable, une couture propre ou une valve efficace évitent beaucoup de frustration.</p><h2>Investir dans la simplicité</h2><p>Un produit bien conçu réduit les gestes inutiles. Il laisse plus de place au paysage, au repas et au repos.</p>',
                'published_at' => now()->subDay(),
            ],
        ])->each(fn (array $post): BlogPost => BlogPost::updateOrCreate(['slug' => $post['slug']], $post + ['is_published' => true]));

        $order = Order::updateOrCreate(
            ['order_number' => 'CV-DEMO-0001'],
            [
                'user_id' => $customer->id,
                'status' => 'terminee',
                'subtotal' => 1028,
                'total' => 1028,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'address_line' => '12 rue des Jasmins',
                'postal_code' => '1002',
                'city' => 'Tunis',
                'country' => 'Tunisie',
            ],
        );

        $order->items()->delete();
        foreach ([$products['tente-aurora-2-places'], $products['rechaud-ember-pro']] as $product) {
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'quantity' => 1,
                'unit_price' => $product->price,
                'total' => $product->price,
            ]);
        }
    }
}
