-- USE nomedb; -- inserite il vostro nome utente

DROP TABLE IF EXISTS domanda_contattaci;
DROP TABLE IF EXISTS item_allergico;
DROP TABLE IF EXISTS ordine_pasticcino;
DROP TABLE IF EXISTS ordine_torta;
DROP TABLE IF EXISTS ordine;
DROP TABLE IF EXISTS persona;
DROP TABLE IF EXISTS allergene;
DROP TABLE IF EXISTS item;

CREATE TABLE persona(
  email varchar(60) NOT NULL,
  nome varchar(20) NOT NULL,
  cognome varchar(20) NOT NULL,
  telefono varchar(20) NOT NULL,
  ruolo varchar(20) NOT NULL,
  password varchar(255) NOT NULL,
  PRIMARY KEY(email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE item (
  id int NOT NULL AUTO_INCREMENT,
  tipo ENUM('torta', 'pasticcino') NOT NULL,    -- iniziali minuscole, non modificare
  nome varchar(30) NOT NULL,
  descrizione varchar(1024),
  prezzo DECIMAL(10,2) NOT NULL, -- Prezzo a porzione (torte) o al pezzo (pasticcini)
  immagine varchar(255),
  testo_alternativo varchar(255),
  attivo boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE ordine (
  id int NOT NULL AUTO_INCREMENT,
  ritiro datetime NOT NULL,
  ordinazione datetime,
  persona varchar(60),
  nome varchar(20),
  cognome varchar(20),
  telefono varchar(20),
  annotazioni varchar(300),
  stato int NOT NULL,
  totale DECIMAL(10,2),
  PRIMARY KEY(id),
  FOREIGN KEY (persona) REFERENCES persona(email) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE ordine_torta (
  torta int NOT NULL,
  ordine int NOT NULL,
  porzioni int NOT NULL,
  targa varchar(255) NOT NULL DEFAULT '',
  numero_torte int NOT NULL DEFAULT 1,
  PRIMARY KEY (torta, ordine, porzioni, targa),
  FOREIGN KEY (torta) REFERENCES item(id),
  FOREIGN KEY (ordine) REFERENCES ordine(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE ordine_pasticcino (
  pasticcino int NOT NULL,
  ordine int NOT NULL,
  quantita int NOT NULL,
  PRIMARY KEY (pasticcino, ordine),
  FOREIGN KEY (pasticcino) REFERENCES item(id),
  FOREIGN KEY (ordine) REFERENCES ordine(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE allergene (
  nome varchar(255) NOT NULL,
  icona varchar(255),
  PRIMARY KEY(nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE item_allergico (
  item int NOT NULL,
  allergene varchar(255) NOT NULL,
  PRIMARY KEY(item, allergene),
  FOREIGN KEY (item) REFERENCES item(id) ON DELETE CASCADE,
  FOREIGN KEY (allergene) REFERENCES allergene(nome) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE domanda_contattaci (
  id int  NOT NULL AUTO_INCREMENT,
  email varchar(60)  NOT NULL,
  domanda text  NOT NULL,
  ip_utente varchar(45) NOT NULL,
  data_invio datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

INSERT INTO persona (email, nome, cognome, telefono, ruolo, password) VALUES
('user','user','user', '3456789012', 'user', '$2y$12$lNuhYtwpLE.XKF4dBOP4tOYbgK.e44PvxwW85eG0snfpS9Dz0a2S6'),
('admin', 'admin', 'admin', '3456789345', 'admin', '$2y$12$swcL0qKYW9Nt0KwIXDBr3ODL6OcMDjDMkFYiW19eZbUs1uYDAvLVe');

-- prezzi torte intesi per 150 grammi ossia 1 porzione
INSERT INTO item (id, tipo ,nome, descrizione, prezzo, immagine, testo_alternativo) VALUES 
(1, 'torta', 'Red Velvet', 'La red velvet è una torta morbida al cacao, dal colore rosso intenso, ricoperta con crema al formaggio.', 3.50, "RedVelvet.jpg", 'Torta Red Velvet composta da pan di Spagna rosso intenso farcito e decorato con crema al formaggio, ciuffi di frosting e briciole rosse'),
(2, 'torta', 'Sachertorte', 'Torta viennese al cioccolato, morbidissima, con glassa fondente e sottile marmellata di albicocche. Capolavoro di pasticceria.', 4.50, "Sacher.jpg", 'Torta Sacher al cioccolato fondente, ricoperta da glassa lucida e decorata con la scritta “Sacher” e ciuffi di cioccolato'),
(3, 'torta', 'Tiramisù', 'Strati di savoiardi inzuppati nel caffè, crema al mascarpone e spolverata di cacao. Semplicemente delizioso.', 4.00, "tiramisu.jpg", 'Torta tiramisù rotonda, con due strati di savoiardi imbevuti di caffè alternati con uno strato di crema al mascarpone, decorata con ciuffi di crema e cacao in polvere'),
(4, 'pasticcino', 'Bignè al Cioccolato', 'Soffici bignè alla panna ricoperti di una lucida glassa al cioccolato. Un classico della pasticceria, golosissimo e leggero.', 1.50, "bigneCioccolato.jpg", 'Bigne al cioccaloto: pasta choux dorata coperta di glassa fondente e con all''interno un cremoso cuore di crema al cacao'),
(5, 'pasticcino', 'Babbà', 'Soffice dolce napoletano, imbevuto di rum, dalla caratteristica forma a fungo. Morbido e irresistibilmente brioso.', 2.00, "babba.jpg", 'Babà napoletano al rum, soffice, a forma di fungo, con superficie lucida, farcito con panna montata e decorato con ciliegia candita e scorze d''arancia'),
(6, 'pasticcino', 'Maritozzo', 'Dolce romano sofficissimo, un panino dolce spaccato e farcito con panna montata abbondante. Semplicemente delizioso.', 3.50, "maritozzo.jpg", 'Composizione a piramide di maritozzi, panini soffice farciti con molta panna montata'),
(7, 'pasticcino', 'Cannolo', 'Cialda croccante ripiena di ricotta setacciata, zuccherata e arricchita con gocce di cioccolato e canditi.', 3.00, "cannolo.jpg", 'Cannolo: cialda croccante di forma cilindrica ripiena di ricotta con con gocce di cioccolato, canditi, ciliegine candite e granella di pistacchio'),
(8, 'torta', 'Torta Pazientina', 'Una torta a strati ricca e raffinata. Una base di pan di Spagna, uno strato di zabaione e uno di crema di mandorle; racchiusa da una copertura di cioccolato fondente. Nata nel Settecento, è considerata la “torta ufficiale” della città di Padova.', 5.00 ,"pazientina.jpg", 'torta pazientina tagliata con strati di pan di spagna, crema zabaione, crema di mandorle e copertura di cioccolato fondente'),
(9, 'torta', 'Dolce del Santo', 'Un dolce lievitato, simile al panettone ma con una personalità tutta padovana. L''impasto è a base di farina, uova, burro, zucchero e miele. Profumato con liquore all''amaretto, vaniglia o scorza d''arancia.', 4.00 ,"dolceSanto.jpg",'Dolce del Santo, dolce lievitato simile al panettone, con uvetta e scorza d''arancia candita, spolverato di zucchero a velo'),
(10, 'torta', 'Millefoglie', 'Tre strati di pasta sfoglia cotta al forno fino a diventare dorata e croccante, alternati con due strati di crema. Una combinazione di croccantezza e cremosità.', 3.80 ,"millefoglie.jpg",'Torta millefoglie composta da tre strati di pasta sfoglia cotta al forno e due strati di crema, decorata con fragole e zucchero a velo'),
(11, 'pasticcino', 'Bussolà padovano', 'Un biscotto a forma di anello, friabile e profumato al burro. Tipico delle colazioni padovane, deriva dalla tradizione veneziana dei "bussolai".', 0.50 ,"bussolaPadovana.jpg",'biscotto a forma di anello, friabile e al burro'),
(12, 'pasticcino', 'Frollino al Mandorlato', 'Pasta frolla croccante arricchita da pezzetti di mandorlato di Cologna Veneta, dolce natalizio veneto a base di mandorle e miele.', 0.50 ,"frollino.jpg",'Frollino croccante con pezzetti di mandorlato'),
(13, 'pasticcino', 'Bigné alla crema', 'Classico della pasticceria: bignè gonfi e dorati, ripieni di crema pasticcera o chantilly e spolverati di zucchero a velo.', 1.50 ,"bigneCrema.jpg",'Bigne alla crema tagliato a metà in pasta choux dorata coperta di glassa al cioccolato bianco e con all''interno un cremoso cuore di crema pasticcera');

INSERT INTO ordine (id, ritiro, ordinazione, persona, nome, cognome, telefono, annotazioni, stato, totale ) VALUES 
(1, '2024-12-19 12:30:00', '2024-12-18 15:30:00', 'user', 'user', 'user', '3456789012', 'aggiungere una candelina', 4, 99.00),
(2, '2024-12-19 13:15:00', '2024-12-18 16:45:00', 'user', 'user', 'user', '3456789012', NULL, 4, 49.50),
(3, '2024-12-19 14:45:00', '2024-12-18 17:30:00', 'user', 'user', 'user', '3456789012', 'voglio delle decorazioni al cioccolato', 4, 53.00),
(4, '2024-12-20 09:30:00', '2024-12-19 18:35:00', 'admin', 'admin', 'admin', '3456789345', NULL, 1, 40.00),
(5, '2024-12-20 11:00:00', '2024-12-19 19:55:37', 'admin', 'admin', 'admin', '3456759345', NULL, 1, 23.00);

INSERT INTO ordine_torta (torta, ordine, porzioni, targa, numero_torte) VALUES 
(1, 1, 8, 'Auguri Sara', 1),
(2, 1, 12, '', 1),
(3, 2, 6, 'Auguri Elia', 1),
(1, 3, 10, '', 1),
(2, 4, 8, 'Auguri Davide', 1);

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
('Uova', NULL),
('Latte', NULL),
('Frutta a guscio', NULL),
('Arachidi', NULL),
('Soia', NULL),
('Sesamo', NULL);

INSERT INTO item_allergico (item, allergene) VALUES 
(1, 'Glutine'), (1, 'Latte'), (1, 'Uova'),
(2, 'Glutine'), (2, 'Uova'), (2, 'Frutta a guscio'),
(3, 'Glutine'), (3, 'Latte'), (3, 'Uova'),
(4, 'Glutine'), (4, 'Latte'), (4, 'Uova'),
(5, 'Glutine'), (5, 'Uova'), (5, 'Latte'),
(6, 'Glutine'), (6, 'Latte'),
(7, 'Glutine'), (7, 'Latte');