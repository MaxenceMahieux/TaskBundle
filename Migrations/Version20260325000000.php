<?php

declare(strict_types=1);

namespace TaskBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tasks table for TaskBundle';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE kimai2_ext_tasks (
            id INT AUTO_INCREMENT NOT NULL,
            project_id INT NOT NULL,
            assignee_id INT DEFAULT NULL,
            created_by_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            priority VARCHAR(20) NOT NULL,
            due_date DATE DEFAULT NULL,
            estimated_minutes INT DEFAULT NULL,
            is_internal TINYINT(1) NOT NULL DEFAULT 0,
            position INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_TASK_PROJECT (project_id),
            INDEX IDX_TASK_ASSIGNEE (assignee_id),
            INDEX IDX_TASK_CREATED_BY (created_by_id),
            INDEX IDX_TASK_PROJECT_STATUS (project_id, status),
            INDEX IDX_TASK_ASSIGNEE_STATUS (assignee_id, status),
            PRIMARY KEY(id),
            CONSTRAINT FK_TASK_PROJECT FOREIGN KEY (project_id) REFERENCES kimai2_projects (id) ON DELETE CASCADE,
            CONSTRAINT FK_TASK_ASSIGNEE FOREIGN KEY (assignee_id) REFERENCES kimai2_users (id) ON DELETE SET NULL,
            CONSTRAINT FK_TASK_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES kimai2_users (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE kimai2_ext_tasks');
    }
}
