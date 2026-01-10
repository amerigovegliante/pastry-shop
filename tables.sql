USE gromanat; 

DROP TABLE IF EXISTS item_allergico;
DROP TABLE IF EXISTS ordine_pasticcino;
DROP TABLE IF EXISTS ordine_torta;
DROP TABLE IF EXISTS ordine;
DROP TABLE IF EXISTS persona;
DROP TABLE IF EXISTS allergene;
DROP TABLE IF EXISTS item;

CREATE TABLE item (
  id int not null,
  tipo varchar(255) not null,
  nome varchar(255) not null,
  icona varchar(255),
  descrizione varchar(255),
  prezzo float not null,
  immagine varchar(255),
  PRIMARY KEY(id)
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE persona(
	email varchar(60) not null,
	nome varchar(20) not null,
	cognome varchar(20) not null,
  telefono int not null,
  ruolo char not null,
  password varchar(20) not null,
  PRIMARY KEY(email)
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE ordine (
  id int not null,
  ritiro datetime not null,
  ordinazione datetime,
  numero int,
  persona varchar(60) not null,
  nome varchar(20),
	cognome varchar(20),
  telefono int,
  annotazioni varchar(300),
  stato int not null,
  totale float,
  PRIMARY KEY(id),
  FOREIGN KEY (persona) REFERENCES persona(email)
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE allergene (
  nome varchar(255) not null,
  icona varchar(255),
  PRIMARY KEY(nome)
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE ordine_torta (
  torta int not null,
  ordine int not null,
  porzioni int not null,
  targa varchar(255),
  foto varchar(255),
  CONSTRAINT pk_ordine_torta primary key (torta, ordine)
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE ordine_pasticcino (
  pasticcino int not null,
  ordine int not null,
  quantita int not null,
  CONSTRAINT pk_ordine_pasticcino primary key (pasticcino, ordine)
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE item_allergico (
  item int not null,
  allergene varchar(255) not null,
  PRIMARY KEY(item, allergene)
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

DELETE FROM item;

INSERT INTO persona (email, nome, cognome, telefono, ruolo, password) VALUES
('user@gmail.com','user','user', 3456789012, 'user', 'user'),
('admin@gmail.com', 'admin', 'admin', 3456789345, 'admin', 'admin');

INSERT INTO item (id, tipo ,nome, icona, descrizione, prezzo, immagine) VALUES 
(1, 'Torta', 'Red Velvet', NULL, 'La red velvet è una torta morbida al cacao, dal colore rosso intenso, ricoperta con crema al formaggio.', 5.00, NULL),
(2, 'Torta', 'Sachertorte', NULL, 'Torta viennese al cioccolato, morbidissima, con glassa fondente e sottile marmellata di albicocche. Capolavoro di pasticceria.', 7.00, NULL),
(3, 'Torta', 'Tiramisù', NULL, 'Strati di savoiardi inzuppati nel caffè, crema al mascarpone e spolverata di cacao. Semplicemente delizioso.', 5.00, NULL),
(4, 'Pasticcino', 'Bignè al Cioccolato', NULL, 'Soffici bignè alla panna ricoperti di una lucida glassa al cioccolato. Un classico della pasticceria, golosissimo e leggero.', 1.50, NULL),
(5, 'Pasticcino', 'Babbà', NULL, 'Soffice dolce napoletano, imbevuto di rum, dalla caratteristica forma a fungo. Morbido e irresistibilmente brioso.', 2.00, NULL),
(6, 'Pasticcino', 'Maritozzo', NULL, 'Dolce romano sofficissimo, un panino dolce spaccato e farcito con panna montata abbondante. Semplicemente delizioso.', 3.50, NULL),
(7, 'Pasticcino', 'Cannolo', NULL, 'Cialda croccante ripiena di ricotta setacciata, zuccherata e arricchita con gocce di cioccolato e canditi.', 3.00, NULL);

INSERT INTO ordine (id, ritiro, ordinazione, numero, persona, nome, cognome, telefono, annotazioni, stato, totale ) VALUES 
(1, '2024-12-19 12:30:00', '2024-12-18 15:30:00', 1, 'user@gmail.com', 'user', 'user', 3456789012, 'aggiungere una candelina', 4, 10),
(2, '2024-12-19 13:15:00', '2024-12-18 16:45:00', 2, 'user@gmail.com', 'user', 'user', 3456789012, NULL, 3, 15),
(3, '2024-12-19 14:45:00', '2024-12-18 17:30:00', 3, 'user@gmail.com', 'user', 'user', 3456789012, 'voglio delle decorazioni al cioccolato', 2, 20),
(4, '2024-12-20 09:30:00', '2024-12-19 18:35:00', 4, 'admin@gmail.com', 'cliente', 'cliente Cognome', 3456789345, NULL, 1, 30),
(5, '2024-12-20 11:00:00', '2024-12-19 19:55:37', 5, 'admin@gmail.com', 'Qualcuno', 'cognome', 3456759345, NULL, 1, 40);


CREATE TABLE ordine (
  id int not null,
  ritiro datetime,
  ordinazione datetime,
  numero int,
  persona varchar(60) not null,
  nome varchar(20) not null,
	cognome varchar(20) not null,
  telefono int not null,
  annotazioni varchar(300),
  PRIMARY KEY(id),
  FOREIGN KEY (persona) REFERENCES persona(email)
) 

INSERT INTO ordine_torta (torta, ordine, porzioni, targa, foto) VALUES 
(1, 1, 8, 'Auguri Sara', NULL),
(2, 1, 12, NULL, NULL), 
(3, 2, 6, 'Auguri Elia', NULL),
(1, 3, 10, NULL, NULL), 
(2, 4, 8, 'Auguri Davide', NULL);

INSERT INTO ordine_pasticcino (pasticcino, ordine, quantita) VALUES 
(4, 1, 6),
(5, 1, 4),
(6, 2, 3),
(7, 2, 5),
(4, 3, 12),
(5, 4, 2),
(6, 5, 4),
(7, 5, 3);

INSERT INTO allergene (nome, icona) VALUES 
('Glutine', NULL),
('Latte', NULL),
('Uova', NULL),
('Soia', NULL),
('Frutta a guscio', NULL),
('Arachidi', NULL);

INSERT INTO item_allergico (item, allergene) VALUES 
(1, 'Glutine'),
(1, 'Latte'),
(1, 'Uova'),
(2, 'Glutine'),
(2, 'Uova'),
(2, 'Frutta a guscio'),
(3, 'Glutine'),
(3, 'Latte'),
(3, 'Uova'),
(4, 'Glutine'),
(4, 'Latte'),
(4, 'Uova'),
(5, 'Glutine'),
(5, 'Uova'),
(5, 'Latte'),
(6, 'Glutine'),
(6, 'Latte'),
(7, 'Glutine'),
(7, 'Latte');
