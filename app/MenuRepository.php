<?php

declare(strict_types=1);

final class MenuRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array{ok:true,currency:string,generated_at:string,categories:list<array{id:int,name:string,slug:string,products:list<array{id:int,name:string,description:string|null,price_toman:int|null,is_available:bool}>}>}
     */
    public function getMenu(): array
    {
        $categories = $this->pdo
            ->query('SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')
            ->fetchAll();

        $menuCategories = [];
        $categoryIds = array_map(static fn (array $category): int => (int) $category['id'], $categories);

        if ($categoryIds !== []) {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $statement = $this->pdo->prepare(
                'SELECT id, category_id, name, description, price_toman, is_available
                 FROM products
                 WHERE category_id IN (' . $placeholders . ')
                 ORDER BY sort_order ASC, id ASC'
            );

            foreach ($categoryIds as $index => $categoryId) {
                $statement->bindValue($index + 1, $categoryId, PDO::PARAM_INT);
            }

            $statement->execute();
            $products = $statement->fetchAll();

            foreach ($categories as $category) {
                $categoryId = (int) $category['id'];
                $menuCategories[] = [
                    'id' => $categoryId,
                    'name' => (string) $category['name'],
                    'slug' => (string) $category['slug'],
                    'products' => [],
                ];
            }

            $positions = [];
            foreach ($menuCategories as $position => $menuCategory) {
                $positions[$menuCategory['id']] = $position;
            }

            foreach ($products as $product) {
                $categoryId = (int) $product['category_id'];
                if (!isset($positions[$categoryId])) {
                    continue;
                }

                $price = $product['price_toman'];
                $menuCategories[$positions[$categoryId]]['products'][] = [
                    'id' => (int) $product['id'],
                    'name' => (string) $product['name'],
                    'description' => $product['description'] === null ? null : (string) $product['description'],
                    'price_toman' => $price === null ? null : (int) $price,
                    'is_available' => (int) $product['is_available'] === 1,
                ];
            }
        }

        return [
            'ok' => true,
            'currency' => 'تومان',
            'generated_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Tehran')))->format('Y-m-d\TH:i:sP'),
            'categories' => $menuCategories,
        ];
    }
}
