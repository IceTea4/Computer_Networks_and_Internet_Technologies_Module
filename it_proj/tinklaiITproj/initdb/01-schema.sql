USE aistis_jakutonis;

-- Table: klausimas
create table if not exists klausimas (
    id binary(16) not null primary key,
    klausimas varchar(500) not null,
    atsakymai varchar(500) not null,
    tema varchar(100) not null,
    verte int not null,
    atsakymas varchar(100) not null,
    index (tema)
);

-- Table: egzaminas
create table if not exists egzaminas (
    id binary(16) not null primary key,
    pavadinimas varchar(100) not null,
    data timestamp null,
    trukme int null,
    bandomasis boolean not null default 0,
    perlaikomo_egzamino_id binary(16) null,
    rezultatu_taisykle varchar(100) null,
    foreign key (perlaikomo_egzamino_id) references egzaminas(id),
    index (data),
    check (rezultatu_taisykle in ('best', 'last', 'average'))
);

-- Table: egzamino_klausimas
create table if not exists egzamino_klausimas (
    id binary(16) not null primary key,
    egzamino_id binary(16) not null,
    klausimo_id binary(16) not null,
    unique key uk_egzamino_klausimas (egzamino_id, klausimo_id),
    foreign key (egzamino_id) references egzaminas(id) on delete cascade,
    foreign key (klausimo_id) references klausimas(id)
);
