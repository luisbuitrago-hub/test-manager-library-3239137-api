
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA library');

        DB::statement('
            CREATE TABLE library.category (
                id UUID PRIMARY KEY,
                name VARCHAR(120) NOT NULL UNIQUE
            )
        ');

        DB::statement("
            INSERT INTO library.category (id, name) VALUES
            ('11111111-1111-4111-8111-111111111111', 'Novela'),
            ('22222222-2222-4222-8222-222222222222', 'Ciencia'),
            ('33333333-3333-4333-8333-333333333333', 'Tecnología'),
            ('44444444-4444-4444-8444-444444444444', 'Historia'),
            ('55555555-5555-4555-8555-555555555555', 'Infantil')
        ");

        DB::statement('
            CREATE TABLE library.book (
                id UUID PRIMARY KEY,
                title VARCHAR(250) NOT NULL,
                author VARCHAR(200) NOT NULL,
                isbn CHAR(13) NOT NULL CHECK (isbn ~ ''^[0-9]{13}$''),
                category_id UUID NOT NULL REFERENCES library.category(id) ON DELETE RESTRICT,
                total_copies INTEGER NOT NULL,
                available_copies INTEGER NOT NULL,
                cover_key VARCHAR(512),
                deleted_at TIMESTAMPTZ,
                CONSTRAINT ck_book_available_range
                    CHECK (available_copies >= 0 AND available_copies <= total_copies),
                CONSTRAINT ck_book_cover_key
                    CHECK (cover_key IS NULL OR length(btrim(cover_key)) > 0)
            )
        ');

        DB::statement('
            CREATE UNIQUE INDEX uq_book_active_isbn
            ON library.book (isbn)
            WHERE deleted_at IS NULL
        ');

        DB::statement('
            CREATE INDEX ix_book_category_title_active
            ON library.book (category_id, title)
            WHERE deleted_at IS NULL
        ');

        DB::statement('
            CREATE TABLE library.member (
                id UUID PRIMARY KEY,
                full_name VARCHAR(200) NOT NULL,
                document_number VARCHAR(30) NOT NULL UNIQUE
            )
        ');

        DB::statement('
            CREATE TABLE library."user" (
                id UUID PRIMARY KEY,
                username VARCHAR(120) NOT NULL UNIQUE,
                password_hash VARCHAR(512) NOT NULL,
                role VARCHAR(40) NOT NULL
            )
        ');

        DB::statement('
            CREATE TABLE library.loan (
                id UUID PRIMARY KEY,
                member_id UUID NOT NULL REFERENCES library.member(id) ON DELETE RESTRICT,
                loaned_by_user_id UUID NOT NULL REFERENCES library."user"(id) ON DELETE RESTRICT,
                loaned_at TIMESTAMPTZ NOT NULL
            )
        ');

        DB::statement('
            CREATE INDEX ix_loan_loaned_at
            ON library.loan (loaned_at)
        ');

        DB::statement('
            CREATE TABLE library.loan_item (
                id UUID PRIMARY KEY,
                loan_id UUID NOT NULL REFERENCES library.loan(id) ON DELETE CASCADE,
                book_id UUID NOT NULL REFERENCES library.book(id) ON DELETE RESTRICT,
                book_title VARCHAR(250) NOT NULL,
                isbn CHAR(13) NOT NULL,
                category_name VARCHAR(120) NOT NULL,
                due_at TIMESTAMPTZ NOT NULL,
                returned_at TIMESTAMPTZ,
                UNIQUE (loan_id, book_id)
            )
        ');

        DB::statement('
            CREATE INDEX ix_loan_item_book_id
            ON library.loan_item (book_id)
        ');

        DB::statement('
            CREATE INDEX ix_loan_item_open_due_at
            ON library.loan_item (due_at)
            WHERE returned_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS library CASCADE');
    }
};