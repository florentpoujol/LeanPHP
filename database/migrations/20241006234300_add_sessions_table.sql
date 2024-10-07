create table leanphp_http_sessions (
    id text constraint users_pk primary key,
    data text null,
    created_at timestamp default current_timestamp not null,
    updated_at timestamp default current_timestamp not null,
    deleted_at timestamp null
);