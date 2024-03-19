SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
--
-- База данных: `stata`
--
--
-- Структура таблицы `inventar`
--

CREATE TABLE `inventar` (
  `row` int(10) NOT NULL COMMENT 'ключ',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id` varchar(14) NOT NULL COMMENT 'номер',
  `date` date NOT NULL COMMENT 'дата',
  `cell` varchar(15) NOT NULL COMMENT 'ячейка',
  `fact` tinyint(1) NOT NULL COMMENT 'тип',
  `user` varchar(20) NOT NULL COMMENT 'оператор',
  `list` text NOT NULL COMMENT 'список шт.кодов',
  `units` int(5) NOT NULL DEFAULT '0' COMMENT 'единиц'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Инвентаризация';


--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `inventar`
--
ALTER TABLE `inventar`
  ADD PRIMARY KEY (`row`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `inventar`
--
ALTER TABLE `inventar`
  MODIFY `row` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ключ', AUTO_INCREMENT=1;
COMMIT;

