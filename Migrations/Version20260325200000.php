<?php

declare(strict_types=1);

namespace TaskBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add task comments, history, attachments, subtasks, recurrences and dependencies';
    }

    public function up(Schema $schema): void
    {
        // Task Comments
        $this->addSql('CREATE TABLE kimai2_ext_task_comments (
            id INT AUTO_INCREMENT NOT NULL,
            task_id INT NOT NULL,
            created_by_id INT NOT NULL,
            message LONGTEXT NOT NULL,
            is_pinned TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_TASK_COMMENT_TASK (task_id),
            INDEX IDX_TASK_COMMENT_USER (created_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE kimai2_ext_task_comments
            ADD CONSTRAINT FK_TASK_COMMENT_TASK FOREIGN KEY (task_id) REFERENCES kimai2_ext_tasks (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE kimai2_ext_task_comments
            ADD CONSTRAINT FK_TASK_COMMENT_USER FOREIGN KEY (created_by_id) REFERENCES kimai2_users (id) ON DELETE CASCADE');

        // Task History
        $this->addSql('CREATE TABLE kimai2_ext_task_history (
            id INT AUTO_INCREMENT NOT NULL,
            task_id INT NOT NULL,
            user_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            field VARCHAR(100) DEFAULT NULL,
            old_value VARCHAR(255) DEFAULT NULL,
            new_value VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_TASK_HISTORY_TASK (task_id),
            INDEX IDX_TASK_HISTORY_USER (user_id),
            INDEX IDX_TASK_HISTORY_DATE (created_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE kimai2_ext_task_history
            ADD CONSTRAINT FK_TASK_HISTORY_TASK FOREIGN KEY (task_id) REFERENCES kimai2_ext_tasks (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE kimai2_ext_task_history
            ADD CONSTRAINT FK_TASK_HISTORY_USER FOREIGN KEY (user_id) REFERENCES kimai2_users (id) ON DELETE CASCADE');

        // Task Attachments
        $this->addSql('CREATE TABLE kimai2_ext_task_attachments (
            id INT AUTO_INCREMENT NOT NULL,
            task_id INT NOT NULL,
            uploaded_by_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            stored_filename VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            size INT NOT NULL,
            uploaded_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_TASK_ATTACHMENT_TASK (task_id),
            INDEX IDX_TASK_ATTACHMENT_USER (uploaded_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE kimai2_ext_task_attachments
            ADD CONSTRAINT FK_TASK_ATTACHMENT_TASK FOREIGN KEY (task_id) REFERENCES kimai2_ext_tasks (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE kimai2_ext_task_attachments
            ADD CONSTRAINT FK_TASK_ATTACHMENT_USER FOREIGN KEY (uploaded_by_id) REFERENCES kimai2_users (id) ON DELETE CASCADE');

        // Task Subtasks
        $this->addSql('CREATE TABLE kimai2_ext_task_subtasks (
            id INT AUTO_INCREMENT NOT NULL,
            task_id INT NOT NULL,
            completed_by_id INT DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            is_completed TINYINT(1) DEFAULT 0 NOT NULL,
            completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            position INT DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_TASK_SUBTASK_TASK (task_id),
            INDEX IDX_TASK_SUBTASK_COMPLETED_BY (completed_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE kimai2_ext_task_subtasks
            ADD CONSTRAINT FK_TASK_SUBTASK_TASK FOREIGN KEY (task_id) REFERENCES kimai2_ext_tasks (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE kimai2_ext_task_subtasks
            ADD CONSTRAINT FK_TASK_SUBTASK_COMPLETED_BY FOREIGN KEY (completed_by_id) REFERENCES kimai2_users (id) ON DELETE SET NULL');

        // Task Recurrences
        $this->addSql('CREATE TABLE kimai2_ext_task_recurrences (
            id INT AUTO_INCREMENT NOT NULL,
            task_id INT NOT NULL,
            frequency VARCHAR(20) NOT NULL,
            interval_value INT DEFAULT 1 NOT NULL,
            days_of_week JSON DEFAULT NULL,
            day_of_month INT DEFAULT NULL,
            start_date DATE NOT NULL,
            end_date DATE DEFAULT NULL,
            next_run_date DATE NOT NULL,
            last_run_date DATE DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            created_tasks INT DEFAULT 0 NOT NULL,
            UNIQUE INDEX UNIQ_TASK_RECURRENCE_TASK (task_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE kimai2_ext_task_recurrences
            ADD CONSTRAINT FK_TASK_RECURRENCE_TASK FOREIGN KEY (task_id) REFERENCES kimai2_ext_tasks (id) ON DELETE CASCADE');

        // Task Dependencies (Many-to-Many self-referential)
        $this->addSql('CREATE TABLE kimai2_ext_task_dependencies (
            task_id INT NOT NULL,
            blocked_by_id INT NOT NULL,
            INDEX IDX_TASK_DEP_TASK (task_id),
            INDEX IDX_TASK_DEP_BLOCKED_BY (blocked_by_id),
            PRIMARY KEY(task_id, blocked_by_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE kimai2_ext_task_dependencies
            ADD CONSTRAINT FK_TASK_DEP_TASK FOREIGN KEY (task_id) REFERENCES kimai2_ext_tasks (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE kimai2_ext_task_dependencies
            ADD CONSTRAINT FK_TASK_DEP_BLOCKED_BY FOREIGN KEY (blocked_by_id) REFERENCES kimai2_ext_tasks (id) ON DELETE CASCADE');

        // Add parent_task_id to tasks table for recurring task instances
        $this->addSql('ALTER TABLE kimai2_ext_tasks ADD parent_task_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE kimai2_ext_tasks ADD INDEX IDX_TASK_PARENT (parent_task_id)');
        $this->addSql('ALTER TABLE kimai2_ext_tasks
            ADD CONSTRAINT FK_TASK_PARENT FOREIGN KEY (parent_task_id) REFERENCES kimai2_ext_tasks (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kimai2_ext_tasks DROP FOREIGN KEY FK_TASK_PARENT');
        $this->addSql('ALTER TABLE kimai2_ext_tasks DROP INDEX IDX_TASK_PARENT');
        $this->addSql('ALTER TABLE kimai2_ext_tasks DROP parent_task_id');

        $this->addSql('DROP TABLE kimai2_ext_task_dependencies');
        $this->addSql('DROP TABLE kimai2_ext_task_recurrences');
        $this->addSql('DROP TABLE kimai2_ext_task_subtasks');
        $this->addSql('DROP TABLE kimai2_ext_task_attachments');
        $this->addSql('DROP TABLE kimai2_ext_task_history');
        $this->addSql('DROP TABLE kimai2_ext_task_comments');
    }
}
