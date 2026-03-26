<?php

declare(strict_types=1);

namespace TaskBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task columns table and update tasks table';
    }

    public function up(Schema $schema): void
    {
        // Create task_columns table
        $this->addSql('CREATE TABLE kimai2_ext_task_columns (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(50) NOT NULL,
            color VARCHAR(7) NOT NULL,
            position INT NOT NULL,
            is_default TINYINT(1) NOT NULL,
            is_closed_status TINYINT(1) NOT NULL,
            UNIQUE INDEX UNIQ_TASK_COLUMN_SLUG (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Insert default columns
        $this->addSql("INSERT INTO kimai2_ext_task_columns (name, slug, color, position, is_default, is_closed_status) VALUES
            ('To Do', 'todo', '#6c757d', 0, 1, 0),
            ('In Progress', 'in_progress', '#0d6efd', 1, 0, 0),
            ('Review', 'review', '#ffc107', 2, 0, 0),
            ('Done', 'done', '#198754', 3, 0, 1)
        ");

        // Add column_id to tasks table
        $this->addSql('ALTER TABLE kimai2_ext_tasks ADD column_id INT NULL');

        // Migrate existing status values to column_id
        $this->addSql("UPDATE kimai2_ext_tasks SET column_id = (SELECT id FROM kimai2_ext_task_columns WHERE slug = status)");

        // Set default column for any tasks without a matching status
        $this->addSql("UPDATE kimai2_ext_tasks SET column_id = (SELECT id FROM kimai2_ext_task_columns WHERE is_default = 1 LIMIT 1) WHERE column_id IS NULL");

        // Make column_id NOT NULL and add foreign key
        $this->addSql('ALTER TABLE kimai2_ext_tasks MODIFY column_id INT NOT NULL');
        $this->addSql('ALTER TABLE kimai2_ext_tasks ADD CONSTRAINT FK_TASK_COLUMN FOREIGN KEY (column_id) REFERENCES kimai2_ext_task_columns (id)');
        $this->addSql('CREATE INDEX IDX_TASK_COLUMN ON kimai2_ext_tasks (column_id)');

        // Drop old status column
        $this->addSql('ALTER TABLE kimai2_ext_tasks DROP COLUMN status');
    }

    public function down(Schema $schema): void
    {
        // Add status column back
        $this->addSql("ALTER TABLE kimai2_ext_tasks ADD status VARCHAR(20) NOT NULL DEFAULT 'todo'");

        // Migrate column_id back to status
        $this->addSql("UPDATE kimai2_ext_tasks t SET t.status = (SELECT c.slug FROM kimai2_ext_task_columns c WHERE c.id = t.column_id)");

        // Drop foreign key and column
        $this->addSql('ALTER TABLE kimai2_ext_tasks DROP FOREIGN KEY FK_TASK_COLUMN');
        $this->addSql('DROP INDEX IDX_TASK_COLUMN ON kimai2_ext_tasks');
        $this->addSql('ALTER TABLE kimai2_ext_tasks DROP COLUMN column_id');

        // Drop task_columns table
        $this->addSql('DROP TABLE kimai2_ext_task_columns');
    }
}
