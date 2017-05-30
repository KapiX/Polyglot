drop table if exists `users`;
create table if not exists `users` (
    `id` int(11) not null auto_increment,
    `name` varchar(64) not null,
    `token` char(64) not null, -- GitHub token, OAuth
    primary key (`id`)
);

drop table if exists `projects`;
create table if not exists `projects` (
    `id` int(11) not null auto_increment,
    `name` varchar(64) not null,
    `language_id` int(11) not null, -- Primary language
    `github` varchar(128), -- link?
    primary key (`id`)
);

-- Enabled languages
drop table if exists `languages_projects`;
create table if not exists `languages_projects` (
    `id` int(11) not null auto_increment,
    `project_id` int(11) not null,
    `language_id` int(11) not null,
    primary key (`id`)
);

drop table if exists `users_projects`;
create table if not exists `users_projects` (
    `id` int(11) not null auto_increment,
    `user_id` int(11) not null,
    `project_id` int(11) not null,
    `type` int(1) not null, -- admin/translator/user
    primary key (`id`)
);

drop table if exists `files`;
create table if not exists `files` (
    `id` int(11) not null auto_increment,
    `project_id` int(11) not null,
    `name` varchar(64) not null,
    `path` varchar(64) not null,
    `checksum` varchar(64) not null,
    primary key (`id`)
);

drop table if exists `strings`;
create table if not exists `strings` (
    `id` int(11) not null auto_increment,
    `string` varchar(64) not null,
    `comment` varchar(64) not null,
    `context` varchar(64) not null,
    primary key (`id`)
);

drop table if exists `languages`;
create table if not exists `languages` (
    `id` int(11) not null auto_increment,
    `iso_code` varchar(64) not null,
    `name` varchar(64) not null,
    primary key (`id`)
);

drop table if exists `translations`;
create table if not exists `translations` (
    `id` int(11) not null auto_increment, -- needed?
    `string_id` int(11) not null,
    `language_id` int(11) not null,
    `author_id` int(11) not null,
    `translation` varchar(64) not null,
    `needs_work` int(1) not null, -- bool
    primary key (`id`)
);
