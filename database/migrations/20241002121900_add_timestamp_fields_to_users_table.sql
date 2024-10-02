alter table users add created_at timestamp default current_timestamp not null;

alter table users add updated_at timestamp default current_timestamp not null;
-- need a trigger to do "on update" with sqlite