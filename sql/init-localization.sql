-- TG Course Bot PRO - Localization Settings Initialization
-- This script creates all localization options with default values
-- Replace 'wpd4_' with your actual table prefix if different

-- Language & Navigation
INSERT INTO wpd4_options (option_name, option_value, autoload) VALUES
('tgcb_msg_menu_header', '👇 <b>Главное меню</b>', 'yes'),
('tgcb_btn_all_courses', '📚 Все курсы', 'yes'),
('tgcb_btn_my_courses', '👤 Мои курсы', 'yes'),
('tgcb_btn_help', '❓ Помощь', 'yes'),
('tgcb_btn_support', '👨💻 Поддержка', 'yes')
ON DUPLICATE KEY UPDATE option_value = VALUES(option_value);

-- Bot Messages
INSERT INTO wpd4_options (option_name, option_value, autoload) VALUES
('tgcb_msg_welcome', '👋 Добро пожаловать, {name}!\n\nПожалуйста, выберите курс из списка:\n\nПосле выбора отправьте скриншот чека об оплате.', 'yes'),
('tgcb_msg_no_courses', 'На данный момент курсы недоступны.', 'yes'),
('tgcb_msg_select_course', '📸 Пожалуйста, отправьте скриншот чека об оплате для:\n{course}', 'yes'),
('tgcb_msg_receipt_received', '✅ Чек получен!\n\nВаш платеж проверяется администратором.\nВы получите ссылку-приглашение после подтверждения.', 'yes'),
('tgcb_msg_approved', '✅ <b>Оплата подтверждена!</b>\n\nВаш доступ к <b>{course}</b> открыт.\n\nНажмите на ссылку ниже, чтобы вступить:\n{link}\n\n⚠️ Эта ссылка одноразовая и действует 24 часа.', 'yes'),
('tgcb_msg_rejected', '❌ <b>Оплата отклонена</b>\n\nК сожалению, ваш платеж не подтвержден.\nПожалуйста, свяжитесь с поддержкой.', 'yes'),
('tgcb_msg_banned', '❌ Вы забанены в этом боте.', 'yes'),
('tgcb_msg_already_joined', '✅ У вас уже есть доступ к этому курсу!', 'yes'),
('tgcb_msg_select_first', '❌ Пожалуйста, сначала выберите курс через /start', 'yes')
ON DUPLICATE KEY UPDATE option_value = VALUES(option_value);

-- My Courses Messages
INSERT INTO wpd4_options (option_name, option_value, autoload) VALUES
('tgcb_msg_my_courses_empty', '👤 <b>Мои курсы</b>\n\nВы еще не записались ни на один курс.\nВыберите ''📚 Все курсы'', чтобы начать!', 'yes'),
('tgcb_msg_my_courses_header', '👤 <b>Мои курсы</b>\n\nУ вас есть доступ к следующим курсам:', 'yes')
ON DUPLICATE KEY UPDATE option_value = VALUES(option_value);

-- Help & Support Messages
INSERT INTO wpd4_options (option_name, option_value, autoload) VALUES
('tgcb_msg_help', '❓ <b>Как использовать бота:</b>\n\n1️⃣ Нажмите <b>📚 Все курсы</b>\n2️⃣ Выберите курс\n3️⃣ Отправьте скриншот оплаты\n4️⃣ Ждите подтверждения\n5️⃣ Получите ссылку!\n\nНажмите <b>👤 Мои курсы</b> для просмотра подписок.', 'yes'),
('tgcb_msg_support', '👨💻 <b>Поддержка</b>\n\nЕсли у вас есть вопросы, напишите администратору.', 'yes')
ON DUPLICATE KEY UPDATE option_value = VALUES(option_value);

-- Invite Link Messages
INSERT INTO wpd4_options (option_name, option_value, autoload) VALUES
('tgcb_msg_invite_header', '🎟 <b>Новая ссылка-приглашение</b>', 'yes'),
('tgcb_msg_invite_body', 'Вот ваша новая ссылка-приглашение для <b>{course}</b>:', 'yes'),
('tgcb_msg_invite_warning', '⚠️ Эта ссылка одноразовая и действует 24 часа.', 'yes')
ON DUPLICATE KEY UPDATE option_value = VALUES(option_value);
