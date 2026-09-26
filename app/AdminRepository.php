<?php

declare(strict_types=1);

final class AdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getSetting(string $name): ?string
    {
        $statement = $this->pdo->prepare('SELECT value FROM settings WHERE name = ?');
        $statement->execute([$name]);
        $value = $statement->fetchColumn();

        return $value === false ? null : (string) $value;
    }

    public function setSetting(string $name, string $value): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO settings (name, value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $statement->execute([$name, $value]);
    }

    public function verifyPassword(string $password): bool
    {
        $hash = $this->getSetting('admin_password_hash');
        if ($hash === null || $hash === '') {
            return false;
        }

        if (!password_verify($password, $hash)) {
            return false;
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->setSetting('admin_password_hash', password_hash($password, PASSWORD_DEFAULT));
        }

        return true;
    }

    public function setPassword(string $password): void
    {
        $this->setSetting('admin_password_hash', password_hash($password, PASSWORD_DEFAULT));
    }

    public function stats(): array
    {
        $row = $this->pdo->query(
            'SELECT
               (SELECT COUNT(*) FROM categories) AS categories,
               (SELECT COUNT(*) FROM categories WHERE is_active = 1) AS active_categories,
               (SELECT COUNT(*) FROM products) AS products,
               (SELECT COUNT(*) FROM products WHERE is_available = 1) AS available,
               (SELECT COUNT(*) FROM products WHERE is_available = 0) AS unavailable'
        );

        $data = $row === false ? [] : (array) $row->fetch();

        return [
            'categories' => (int) ($data['categories'] ?? 0),
            'active_categories' => (int) ($data['active_categories'] ?? 0),
            'products' => (int) ($data['products'] ?? 0),
            'available' => (int) ($data['available'] ?? 0),
            'unavailable' => (int) ($data['unavailable'] ?? 0),
        ];
    }

    public function unavailableProducts(): array
    {
        return $this->pdo->query(
            'SELECT p.id, p.name, c.name AS category_name
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.is_available = 0
             ORDER BY p.updated_at DESC, p.id DESC'
        )->fetchAll();
    }

    public function recentlyUpdated(int $limit = 8): array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.name, p.price_toman, p.is_available, p.updated_at, c.name AS category_name
             FROM products p
             JOIN categories c ON c.id = p.category_id
             ORDER BY p.updated_at DESC, p.id DESC
             LIMIT ?'
        );
        $statement->bindValue(1, $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function listCategories(): array
    {
        $categories = $this->pdo->query(
            'SELECT c.id, c.name, c.slug, c.sort_order, c.is_active,
                    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
             FROM categories c
             ORDER BY c.sort_order ASC, c.id ASC'
        )->fetchAll();

        return $categories === false ? [] : $categories;
    }

    public function getCategory(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name, slug, sort_order, is_active FROM categories WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function categorySlugExists(string $slug, int $exceptId = 0): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM categories WHERE slug = ? AND id <> ?');
        $statement->execute([$slug, $exceptId]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function createCategory(string $name, string $slug, int $sortOrder, bool $isActive): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO categories (name, slug, sort_order, is_active) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$name, $slug === '' ? 'cat-temp' : $slug, $sortOrder, $isActive ? 1 : 0]);
        $id = (int) $this->pdo->lastInsertId();

        if ($slug === '') {
            $statement = $this->pdo->prepare('UPDATE categories SET slug = ? WHERE id = ?');
            $statement->execute(['cat-' . $id, $id]);
        }

        return $id;
    }

    public function updateCategory(int $id, string $name, string $slug, int $sortOrder, bool $isActive): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE categories SET name = ?, slug = ?, sort_order = ?, is_active = ? WHERE id = ?'
        );
        $statement->execute([$name, $slug, $sortOrder, $isActive ? 1 : 0, $id]);
    }

    public function categoryProductCount(int $id): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $statement->execute([$id]);

        return (int) $statement->fetchColumn();
    }

    public function deleteCategory(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM categories WHERE id = ?');
        $statement->execute([$id]);
    }

    public function toggleCategoryActive(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE categories SET is_active = 1 - is_active WHERE id = ?');
        $statement->execute([$id]);
    }

    public function listProducts(?int $categoryId = null): array
    {
        if ($categoryId === null) {
            $rows = $this->pdo->query(
                'SELECT p.id, p.category_id, p.name, p.slug, p.description, p.price_toman,
                        p.is_available, p.sort_order, c.name AS category_name
                 FROM products p
                 JOIN categories c ON c.id = p.category_id
                 ORDER BY c.sort_order ASC, c.id ASC, p.sort_order ASC, p.id ASC'
            )->fetchAll();

            return $rows === false ? [] : $rows;
        }

        $statement = $this->pdo->prepare(
            'SELECT p.id, p.category_id, p.name, p.slug, p.description, p.price_toman,
                    p.is_available, p.sort_order, c.name AS category_name
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.category_id = ?
             ORDER BY p.sort_order ASC, p.id ASC'
        );
        $statement->execute([$categoryId]);
        $rows = $statement->fetchAll();

        return $rows === false ? [] : $rows;
    }

    public function getProduct(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, category_id, name, slug, description, price_toman, is_available, sort_order
             FROM products WHERE id = ?'
        );
        $statement->execute([$id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function productSlugExists(int $categoryId, string $slug, int $exceptId = 0): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM products WHERE category_id = ? AND slug = ? AND id <> ?'
        );
        $statement->execute([$categoryId, $slug, $exceptId]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function createProduct(
        int $categoryId,
        string $name,
        string $slug,
        ?string $description,
        ?int $price,
        bool $isAvailable,
        int $sortOrder
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO products (category_id, name, slug, description, price_toman, is_available, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bindValue(1, $categoryId, PDO::PARAM_INT);
        $statement->bindValue(2, $name);
        $statement->bindValue(3, $slug === '' ? 'item-temp' : $slug);
        $statement->bindValue(4, $description);
        $statement->bindValue(5, $price, $price === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $statement->bindValue(6, $isAvailable ? 1 : 0, PDO::PARAM_INT);
        $statement->bindValue(7, $sortOrder, PDO::PARAM_INT);
        $statement->execute();
        $id = (int) $this->pdo->lastInsertId();

        if ($slug === '') {
            $statement = $this->pdo->prepare('UPDATE products SET slug = ? WHERE id = ?');
            $statement->execute(['item-' . $id, $id]);
        }

        return $id;
    }

    public function updateProduct(
        int $id,
        int $categoryId,
        string $name,
        string $slug,
        ?string $description,
        ?int $price,
        bool $isAvailable,
        int $sortOrder
    ): void {
        $statement = $this->pdo->prepare(
            'UPDATE products
             SET category_id = ?, name = ?, slug = ?, description = ?,
                 price_toman = ?, is_available = ?, sort_order = ?
             WHERE id = ?'
        );
        $statement->bindValue(1, $categoryId, PDO::PARAM_INT);
        $statement->bindValue(2, $name);
        $statement->bindValue(3, $slug);
        $statement->bindValue(4, $description);
        $statement->bindValue(5, $price, $price === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $statement->bindValue(6, $isAvailable ? 1 : 0, PDO::PARAM_INT);
        $statement->bindValue(7, $sortOrder, PDO::PARAM_INT);
        $statement->bindValue(8, $id, PDO::PARAM_INT);
        $statement->execute();
    }

    public function deleteProduct(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM products WHERE id = ?');
        $statement->execute([$id]);
    }

    public function toggleAvailability(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE products SET is_available = 1 - is_available WHERE id = ?');
        $statement->execute([$id]);
    }

    public static function cleanSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }
}
