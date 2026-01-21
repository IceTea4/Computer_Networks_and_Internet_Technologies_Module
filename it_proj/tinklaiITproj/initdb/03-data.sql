USE aistis_jakutonis;

-- Insert default administrator user
-- Username: admin
-- Password: admin
-- Role: administratorius

INSERT IGNORE INTO vartotojas (id, vardas, slaptazodis, role)
VALUES (
    UNHEX(REPLACE(UUID(), '-', '')),
    'admin',
    'ccA1cPYGXls7cU//tTNh3aQtTujuBBngsCHmag2Qr/4=',
    'administratorius'
);
