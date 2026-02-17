<?php
declare(strict_types=1);

use app\Migration;

class m260217_150256_create_posts_table extends Migration
{
    public function up(): void
    {
        $this->createTable('posts', [
            'id' => $this->pk(),
            'title' => $this->text(),
            'content' => $this->text(),
            'created_at' => $this->timestamp() . ' ' . $this->notNull() . ' ' . $this->defaultExpr('CURRENT_TIMESTAMP'),
        ]);
    }

    public function down(): void
    {
        $this->dropTable('posts');
    }
}
