# Task Management Plugin for Kimai

A comprehensive task management plugin with Kanban board, subtasks, dependencies, recurring tasks, and more.

## Features

- **Kanban Board** - Visual task management with drag-and-drop
- **Task List View** - Traditional list view with advanced filters
- **Subtasks** - Break down tasks into smaller items
- **Task Dependencies** - Define blocking relationships between tasks
- **Comments** - Collaborate with pinnable comments
- **File Attachments** - Attach documents (PDF, images, Office files)
- **Task History** - Full audit trail of changes
- **Recurring Tasks** - Automate task creation (daily, weekly, monthly, yearly)
- **Bulk Actions** - Move, assign, or delete multiple tasks at once
- **Time Tracking Integration** - Track time directly from tasks
- **Dashboard Widgets** - My tasks, overdue tasks, tasks by status
- **Email Notifications** - Get notified on assignment and due dates

## Requirements

- Kimai 2.52.0 or higher
- PHP 8.1 or higher

## Installation

### Via Kimai Plugins Directory

1. Download the plugin from the Kimai store
2. Extract to `var/plugins/TaskBundle/`
3. Clear cache: `bin/console cache:clear`
4. Run migrations: `bin/console kimai:bundle:task:install`

### Via Composer

```bash
composer require plugiit/task-bundle
bin/console cache:clear
bin/console kimai:bundle:task:install
```

## Configuration

### Customize Columns

Navigate to **Tasks > Columns** to create and manage your workflow columns (e.g., To Do, In Progress, Done).

### Recurring Tasks

Set up a cron job to process recurring tasks:

```bash
# Run every hour
0 * * * * /path/to/kimai/bin/console task:recurring:process
```

## Screenshots

### Kanban Board
Drag and drop tasks between columns with visual priority indicators.

### Task Details
Full task view with subtasks, comments, attachments, dependencies, and history.

## Permissions

| Permission | Description |
|------------|-------------|
| `task_view` | View tasks |
| `task_create` | Create new tasks |
| `task_edit` | Edit tasks |
| `task_delete` | Delete tasks |
| `task_admin` | Full administrative access |

## Translations

- English
- French

## Support

- GitHub Issues: [Report a bug](https://github.com/plugiit/kimai-task-bundle/issues)
- Website: [plugiit.com](https://plugiit.com)

## License

MIT License - see [LICENSE](LICENSE) for details.

## Author

Maxence - [Plugiit](https://plugiit.com)
