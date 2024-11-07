<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTheaters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('theaters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable(false);
            $table->string('name', 255);
            $table->string('address', 255);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::unprepared('
            CREATE TRIGGER tr_theaters_uuid BEFORE INSERT ON `theaters` FOR EACH ROW
                BEGIN
                    if NEW.uuid =\'\' || NEW.uuid IS NULL then
                    SET new.uuid = uuid();
                    end if;
                END
        ');

        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable(false);
            $table->string('name', 255);
            $table->text('description');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::unprepared('
            CREATE TRIGGER tr_movies_uuid BEFORE INSERT ON `movies` FOR EACH ROW
                BEGIN
                    if NEW.uuid =\'\' || NEW.uuid IS NULL then
                    SET new.uuid = uuid();
                    end if;
                END
        ');

        Schema::create('movie_sales', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable(false);
            $table->bigInteger('theater_id')->unsigned();
            $table->bigInteger('movie_id')->unsigned();
            $table->date('sale_date');
            $table->decimal('price')->unsigned();
            $table->timestamps();
            $table->foreign('theater_id')->references('id')->on('theaters')->onDelete('cascade');
            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');
        });

        DB::unprepared('
            CREATE TRIGGER tr_movie_sales_uuid BEFORE INSERT ON `movie_sales` FOR EACH ROW
                BEGIN
                    if NEW.uuid =\'\' || NEW.uuid IS NULL then
                    SET new.uuid = uuid();
                    end if;
                END
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movie_sales');
        Schema::dropIfExists('movies');
        Schema::dropIfExists('theaters');
    }
}
