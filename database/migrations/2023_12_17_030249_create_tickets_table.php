    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('tickets', function (Blueprint $table) {
                $table->foreignId("trip_id");
                $table->unsignedBigInteger("ticket_number");
                $table->unsignedBigInteger("seat_number");
                $table->foreignId("customer_id");
                $table->enum("payment_method", ["cash", "visa"])->default("cash");
                $table->timestamp("created_at")->useCurrent();

                $table->primary(["trip_id", "ticket_number"]);
            });

            Schema::table('tickets', function (Blueprint $table) {
                $table->foreign("trip_id")->references("id")->on("trips")->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreign("customer_id")->references("id")->on("customers")->cascadeOnDelete()->cascadeOnUpdate();
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::dropIfExists('tickets');
        }
    };
