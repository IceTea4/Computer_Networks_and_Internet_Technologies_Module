USE aistis_jakutonis;

-- Table: vartotojas
create table if not exists vartotojas (
    id binary(16) not null primary key,
    vardas varchar(100) not null unique,
    slaptazodis varchar(100) not null,
    role enum('administratorius', 'destytojas', 'vartotojas') not null,
    index (vardas),
    index (role)
);

-- Table: egzamino_atsakymas
create table if not exists egzamino_atsakymas (
    id binary(16) not null primary key,
    vartotojo_id binary(16) not null,
    egzamino_klausimo_id binary(16) not null,
    atsakymas varchar(100) not null,
    unique key uk_user_exam_question (vartotojo_id, egzamino_klausimo_id),
    foreign key (vartotojo_id) references vartotojas(id),
    foreign key (egzamino_klausimo_id) references egzamino_klausimas(id),
    index (vartotojo_id, egzamino_klausimo_id)
);

-- Table: egzamino_rezultatas
create table if not exists egzamino_rezultatas (
    id binary(16) not null primary key,
    vartotojo_id binary(16) not null,
    egzamino_id binary(16) not null,
    verte int not null,
    data timestamp not null,
    perlaikomas boolean not null,
    foreign key (vartotojo_id) references vartotojas(id),
    foreign key (egzamino_id) references egzaminas(id),
    index (vartotojo_id, egzamino_id)
);
