<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable(false);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->integer('login_count')->default(0);
            $table->tinyInteger('login_failed_attempts')->default(0);
            $table->tinyInteger('is_pw_reset_required')->default(0);
            $table->tinyInteger('is_email_verified')->default(0);
            $table->tinyInteger('is_sms_verified')->default(0);
            $table->tinyInteger('is_locked')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('lastlogin_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::unprepared('
            CREATE TRIGGER tr_users_uuid BEFORE INSERT ON `users` FOR EACH ROW
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
        Schema::dropIfExists('users');
    }
};
