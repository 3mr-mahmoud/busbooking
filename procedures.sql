DELIMITER // 

CREATE PROCEDURE update_admin (
IN adminid BIGINT(20),
IN updatedname VARCHAR(255),
IN updatedphone BIGINT(20),
IN updatedemail VARCHAR(255),
IN updatedpassword VARCHAR(255),
IN updatedsuperadmin TINYINT
)
BEGIN
UPDATE admins 
SET name = updatedname, phone = updatedphone,
         email = updatedemail, password = updatedpassword,
        superadmin = updatedsuperadmin
        WHERE id = adminid;

END //



CREATE PROCEDURE select_admin (
IN adminid BIGINT(20)
)
BEGIN
	select * from admins where id = adminid;
END //




CREATE PROCEDURE select_stations (
IN routid BIGINT(20)
)
BEGIN
Select stations.name, stations.id ,route_station.`order` 
from route_station LEFT JOIN stations 
ON stations.id = route_station.station_id 
where route_station.route_id = routid 
ORDER BY route_station.`order` ASC;
END //




CREATE PROCEDURE select_superadmin_flag (
IN adminid BIGINT(20)
)
BEGIN
	select superadmin from admins where id =adminid ;
END //


CREATE PROCEDURE insert_admin_token (
IN adminid BIGINT(20),
IN ttok VARCHAR(255)
)
BEGIN
	INSERT INTO admin_tokens (admin_id,token) VALUES (adminid,ttok);
END //



CREATE PROCEDURE select_bus_with_creator (
IN busid BIGINT(20)
)
BEGIN
	Select buses.*,
         admins.name as creator_name from buses 
         LEFT JOIN admins ON buses.created_by = admins.id 
         where buses.id = busid;
END //





CREATE PROCEDURE select_bus (
IN busid BIGINT(20)
)
BEGIN
Select id, (select count(1) from bus_seats where bus_id = busid) 
as seats from buses where buses.id = busid;
END //






CREATE PROCEDURE delete_seats_greaterthan (
IN busid BIGINT(20),
IN seatnum BIGINT(20)
)
BEGIN
DELETE FROM bus_seats WHERE bus_id = busid AND seat_number > seatnum;
END //






CREATE PROCEDURE insert_bus (
IN platenumber VARCHAR(255),
IN busmodel VARCHAR(255),
in buscapacity DECIMAL,
IN buscategoryid BIGINT,
IN adminid BIGINT

)
BEGIN
Insert INTO buses (plate_number, model, capacity, bus_category_id, created_by) 
                Values (platenumber , busmodel, buscapacity, buscategoryid, adminid);
END //






CREATE PROCEDURE select_routes (
IN routid BIGINT(20)
)
BEGIN
Select routes.*, admins.name as creator_name from routes LEFT JOIN admins ON routes.created_by = admins.id where routes.id = routid;
END //

DELIMITER ;