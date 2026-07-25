CREATE TABLE IF NOT EXISTS personal_tasks (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  client_id     BIGINT NOT NULL,          -- the Date.now()-based id the frontend already generates
  name          VARCHAR(500) NOT NULL,
  person        VARCHAR(255) DEFAULT '',
  collab        VARCHAR(255) DEFAULT '',
  urgency       VARCHAR(20)  NOT NULL DEFAULT 'medium',
  damage        VARCHAR(20)  NOT NULL DEFAULT 'moderate',
  rank_val      VARCHAR(20)  NOT NULL DEFAULT 'medium',
  score         INT NOT NULL DEFAULT 0,
  deadline      DATE NULL,
  recur         VARCHAR(30) DEFAULT 'none',
  tags          JSON NULL,
  links         JSON NULL,
  steps         JSON NULL,
  pinned        TINYINT(1) NOT NULL DEFAULT 0,
  done          TINYINT(1) NOT NULL DEFAULT 0,
  done_at       DATETIME NULL,
  added_on      DATETIME NOT NULL,
  order_num     BIGINT NOT NULL DEFAULT 0,
  time_spent    BIGINT NOT NULL DEFAULT 0,
  time_tracking_started BIGINT NULL,
  updated_at    BIGINT NOT NULL,          -- ms epoch, used as last-write-wins guard
  PRIMARY KEY (id),
  UNIQUE KEY uniq_user_client (user_id, client_id),
  KEY idx_user_order (user_id, order_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;