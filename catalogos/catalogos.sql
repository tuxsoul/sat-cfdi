-- Parse::SQL::Dia     version 0.29                              
-- Documentation       http://search.cpan.org/dist/Parse-Dia-SQL/
-- Environment         Perl 5.026001, /usr/bin/perl              
-- Architecture        x86_64-linux-gnu-thread-multi             
-- Target Database     sqlite3                                   
-- Input file          catalogos.dia                             
-- Generated at        Wed Dec 27 00:25:03 2017                  
-- Typemap for sqlite3 not found in input file                   

-- get_constraints_drop 

-- get_permissions_drop 

-- get_view_drop

-- get_schema_drop
drop table if exists tbl_uso_cfdi;
drop table if exists tbl_concepto;
drop table if exists tbl_unidad;

-- get_smallpackage_pre_sql 

-- get_schema_create

create table tbl_uso_cfdi (
   clave           text    not null  ,
   descripcion     text    not null  ,
   fisica          integer  default 0,
   moral           integer  default 0,
   vigencia_inicio text              ,
   vigencia_fin    text              ,
   version         real              ,
   revision        integer           ,
   id              integer not null  ,
   constraint pk_tbl_uso_cfdi primary key (id)
)   ;

create table tbl_concepto (
   clave           text    not null,
   descripcion     text    not null,
   iva             text            ,
   ieps            text            ,
   complemento     text            ,
   vigencia_inicio text            ,
   vigencia_fin    text            ,
   version         real            ,
   revision        integer         ,
   id              integer not null,
   constraint pk_tbl_concepto primary key (id)
)   ;

create table tbl_unidad (
   clave           text    not null,
   nombre          text    not null,
   vigencia_inicio text            ,
   vigencia_fin    text            ,
   version         real            ,
   revision        integer         ,
   id              integer not null,
   constraint pk_tbl_unidad primary key (id)
)   ;

-- get_view_create

-- get_permissions_create

-- get_inserts

-- get_smallpackage_post_sql

-- get_associations_create
