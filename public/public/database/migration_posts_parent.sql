-- 楼中楼：回复可指向某条评论（parent_post_id）。执行前 USE 你的库名。

ALTER TABLE posts
  ADD COLUMN parent_post_id INT UNSIGNED NULL DEFAULT NULL AFTER topic_id,
  ADD KEY idx_posts_parent (parent_post_id),
  ADD CONSTRAINT fk_posts_parent FOREIGN KEY (parent_post_id) REFERENCES posts (id) ON DELETE CASCADE;
