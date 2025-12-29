create table item (
  id int not null,
  nome varchar(255) not null,
  icona varchar(255),
  descrizione varchar(255),
  prezzo float not null,
  immagine varchar(255),
  tipo char not null,
  primary key(id)
);

create table ordine (
  id int not null,
  ritiro datetime,
  numero int,
  persona varchar(60) not null,
  primary key(id),
  FOREIGN KEY (persona) REFERENCES persona(Email)
);

create table allergene (
  nome varchar(255) not null,
  icona varchar(255),
  primary key(nome)
);

create table ordine_torta (
  torta int not null,
  ordine int not null,
  porzioni int not null,
  targa varchar(255),
  foto varchar(255),
  constraint id primary key (torta, ordine)
);

create table ordine_pasticcino (
  pasticcino int not null,
  ordine int not null,
  quantita int not null,
  constraint id primary key (pasticcino, ordine)
);

create table item_allergico (
  item int not null,
  allergene varchar(255) not null,
  primary key(item)
);

CREATE TABLE Persona(
	Email VARCHAR(60) PRIMARY KEY,
	Nome VARCHAR(20) NOT NULL,
	Cognome VARCHAR(20) NOT NULL,
  Ruolo CHAR NOT NULL,
  Username VARCHAR(20) NOT NULL,
  Password VARCHAR(20) NOT NULL,
);

delete from item;

INSERT INTO Persona ('Email', 'Nome', 'Cognome', 'Username', 'Password', 'Ruolo') VALUES
('user@gmail.com','user','user','user', 'user', 'U'),
('admin@gmail.com', 'admin', 'admin', 'admin', 'admin', 'A'),

INSERT INTO item (id, nome, icona, descrizione, prezzo, immagine, tipo) VALUES 
(1, 'Red Velvet', NULL, 'La red velvet è una torta morbida al cacao, dal colore rosso intenso, ricoperta con crema al formaggio.', 5.00, NULL,'T'),
(2, 'Sachertorte', NULL, 'Torta viennese al cioccolato, morbidissima, con glassa fondente e sottile marmellata di albicocche. Capolavoro di pasticceria.', 7.00, NULL,'T'),
(3, 'Tiramisù', NULL, 'Strati di savoiardi inzuppati nel caffè, crema al mascarpone e spolverata di cacao. Semplicemente delizioso.', 5.00, NULL,'T'),
(4, 'Bignè al Cioccolato', NULL, 'Soffici bignè alla panna ricoperti di una lucida glassa al cioccolato. Un classico della pasticceria, golosissimo e leggero.', 1.50, NULL,'P'),
(5, 'Babbà', NULL, 'Soffice dolce napoletano, imbevuto di rum, dalla caratteristica forma a fungo. Morbido e irresistibilmente brioso.', 2.00, NULL,'P'),
(6, 'Maritozzo', NULL, 'Dolce romano sofficissimo, un panino dolce spaccato e farcito con panna montata abbondante. Semplicemente delizioso.', 3.50, NULL,'P'),
(7, 'Cannolo', NULL, 'Cialda croccante ripiena di ricotta setacciata, zuccherata e arricchita con gocce di cioccolato e canditi.', 3.00, NULL,'P');

INSERT INTO ordine (id, ritiro, numero) VALUES 
(1, '2024-12-19 12:30:00', 'ORD001'),
(2, '2024-12-19 13:15:00', 'ORD002'),
(3, '2024-12-19 14:45:00', 'ORD003'),
(4, '2024-12-20 09:30:00', 'ORD004'),
(5, '2024-12-20 11:00:00', 'ORD005');

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
