import au.com.bytecode.opencsv.CSVReader;
// import com.google.common.collect.ImmutableMap;

import java.io.FileReader;
import java.io.IOException;
import java.io.StringWriter;
import java.io.FileWriter;
import java.io.BufferedWriter;
import java.io.PrintWriter;
import java.io.File;
import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.TimeZone;
import java.util.UUID;
import java.util.Arrays;
import java.util.ArrayList;
import java.util.Locale;

class Main {
        
    public static final String COL_EVENT_NAME = "Event Name";
    public static final String COL_EVENT_ID = "Event ID";
    public static final String COL_ORG_NAME = "Organizer Name";
    public static final String COL_NUM_ATTENDEES = "Attendee #";
    public static final String COL_1 = "Barcode #";
    public static final String COL_ORDER_DATE = "Order Date";
    public static final String COL_BUYER_FIRST_NAME = "Buyer Last Name ";
    public static final String COL_BUYER_LAST_NAME = "Buyer First Name";
    public static final String COL_BUYER_EMAIL = "Buyer Email ";
    public static final String COL_PREFIX = "Prefix";
    public static final String COL_LAST_NAME = "Last Name";
    public static final String COL_FIRST_NAME = "First Name";
    public static final String COL_SUFFIX = "Suffix";
    public static final String COL_EMAIL = "Email";
    public static final String COL_11 = "Quantity";
    public static final String COL_TICKET_TYPE = "Ticket Type";
    public static final String COL_13 = "Date Attending";
    public static final String COL_14 = "Device #";
    public static final String COL_15 = "Check-In Date";
    public static final String COL_IP_LOC = "IP Location";
    public static final String COL_17 = "Discount";
    public static final String COL_18 = "Group";
    public static final String COL_PROMO_CODE = "Affiliate";
    public static final String COL_ORDER_NUM = "Order #";
    public static final String COL_21 = "Order Type";
    public static final String COL_22 = "Currency";
    public static final String COL_TOTAL_PAID = "Total Paid";
    public static final String COL_24 = "Fees Paid";
    public static final String COL_25 = "Eventbrite Fees ";
    public static final String COL_26 = "Eventbrite Payment Processing";
    public static final String COL_27 = "Tax Paid";
    public static final String COL_28 = "Attendee Status ";
    public static final String COL_29 = "Ticket Delivery Method";
    public static final String COL_HOME_ADDR_1 = "Home Address 1";
    public static final String COL_HOME_ADDR_2 = "Home Address 2";
    public static final String COL_HOME_CITY = "Home City";
    public static final String COL_HOME_STATE = "Home State";
    public static final String COL_HOME_ZIP = "Home Zip";
    public static final String COL_HOME_COUNTRY = "Home Country";
    public static final String COL_HOME_PHONE = "Home Phone";
    public static final String COL_CELL_PHONE = "Cell Phone";
    public static final String COL_GENDER = "Gender";
    public static final String COL_AGE = "Age";
    public static final String COL_BIRTHDAY = "Birth Date";
    public static final String COL_SHIP_ADDR_1 = "Shipping Address 1";
    public static final String COL_SHIP_ADDR_2 = "Shipping Address 2";
    public static final String COL_SHIP_CITY = "Shipping City";
    public static final String COL_SHIP_STATE = "Shipping State";
    public static final String COL_SHIP_ZIP = "Shipping Zip";
    public static final String COL_SHIP_COUNTRY= "Shipping Country";
    public static final String COL_JOB_TITLE = "Job Title";
    public static final String COL_COMPANY = "Company";
    public static final String COL_WORK_ADDR_1 = "Work Address 1";
    public static final String COL_WORK_ADDR_2 = "Work Address 2";
    public static final String COL_WORK_CITY = "Work City";
    public static final String COL_WORK_STATE = "Work State";
    public static final String COL_WORK_ZIP = "Work Zip";
    public static final String COL_WORK_COUNTRY = "Work Country";
    public static final String COL_WORK_PHONE = "Work Phone";
    public static final String COL_WEBSITE = "Website";
    public static final String COL_BLOG = "Blog";
    public static final String COL_NOTES = "Notes";

    //Index of CSV table column names
    private static ArrayList<String> INPUT_HEADERS;

    private static String USERS_FILE=""; //"full_data.csv";
    private static String OUTPUT_SQL_FILE=""; //"import_users.sql";

    //---------------------------------------------------------------------------------------

    //Custom questions: prompt and type values
    private static String Q_1_PROMPT = "Please suggest a topic for an unconference session";
    private static String Q_1_TYPE = "text";

    private static String Q_2_PROMPT = "Would you like to receive BigData-related emails from our event sponsors.";
    private static String Q_2_TYPE = "checkbox";

    // ---------------------- SQL TEMPATES ------------------------------------------------

    private static final String INSERT_TEMPLATE = "INSERT INTO `campsite`.`fos_user` (`username_canonical`,`email_canonical`) VALUES(\"%s\",\"%s\");\n";
    private static final String UPDATE_TEMPLATE = "UPDATE fos_user SET `%s` = IF(%s IS NULL OR %s = '', %s, %s) WHERE email_canonical = '%s';\n";
    
    //group_events_attendees: groupevent_id, email,
    //pd_groups_members: group_id, email,
    //group_event_rsvp_actions: event_id, email, rsvp_date, external_event_id, ticket_type, promo_code, amount_paid
    private static final String ATTEND_TEMPLATE = 
    "INSERT INTO group_events_attendees (" +
        "groupevent_id, " +
        "user_id" + 
    ") VALUES ( " + 
        "%d, " +
        "(SELECT id FROM fos_user WHERE email_canonical = \"%s\") " + 
    ");\n" +

    "INSERT INTO pd_groups_members (" +
        "group_id, " + 
        "user_id" + 
    ") VALUES ( " +
        "(SELECT group_id FROM group_event WHERE id = \"%d\"), " +
        "(SELECT id FROM fos_user WHERE email_canonical = \"%s\") " + 
    ");\n" +

    "INSERT INTO group_event_rsvp_actions (" + 
        "event_id, " + 
        "user_id, " + 
        "rsvp_at, " + 
        "updated_at, " + 
        "created_at, " + 
        "attendance, " + 
        "imported_from, " +
        "external_event_id, " +
        "ticket_type, " +
        "promo_code, " +
        "amount_paid" +
    ") VALUES ( " + 
        "%d, " + 
        "(SELECT id FROM fos_user WHERE email_canonical = \"%s\"), " +
        "\"%s\", " + 
        "now(), " + 
        "now(), " + 
        "\"ATTENDING_YES\", " + 
        "\"Eventbrite\", " +
        "%s, " +
        "\"%s\", " +
        "\"%s\", " +
        "%s" +
    ");\n";

    private static final String UPDATE_ATTENDEES_TEMPLATE = 
    "UPDATE group_event SET attendeeCount = " + 
        "(SELECT COUNT(groupevent_id) FROM group_events_attendees WHERE groupevent_id = \"%d\") " + 
    "WHERE id = \"%d\";\n";

    //%d=event_id, %s=question, %s=type
    private static final String CUSTOM_QUESTION_TEMPLATE = 
    "INSERT INTO `campsite`.`registration_field`(`event_id`,`question`,`type`) " + 
    "VALUES(%d,\"%s\",'%s');\n";

    // %d=event_id, %s=question, %s=email, %s=answer
    private static final String CUSTOM_ANSWER_TEMPLATE =
    "INSERT INTO `campsite`.`registration_answer` (`field_id`, `user_id`, `answer`) " +
    "VALUES ( " +
        "(SELECT id FROM registration_field " +
            "WHERE event_id=%d AND question=\"%s\" LIMIT 1), " +
        "(SELECT id FROM fos_user WHERE email_canonical = \"%s\"), " +
        "\"%s\"" +
    ");\n";

    //---------------------------------- Helper Methods -----------------------------------------

    public static String getCell(String[] row_data, String column_name) {
        int column_index = INPUT_HEADERS.indexOf(column_name);
        if(column_index == -1)
            return "";

        return row_data[column_index];
    }

    public static String hash(String s, String algo) {
        try {
            java.security.MessageDigest md = java.security.MessageDigest.getInstance(algo);
            byte[] array = md.digest(s.getBytes("UTF-8"));
            StringBuffer sb = new StringBuffer();
            for (int i = 0; i < array.length; ++i) {
                //magical conver to string code
                sb.append(Integer.toHexString((array[i] & 0xFF) | 0x100).substring(1,3));
            }
            return sb.toString();
        } catch (Exception e) { 
            e.printStackTrace(); 
        }
        return null;
    }

    public static String generateSalt() {
        int unix_time = (int) (System.currentTimeMillis() / 1000L);
        return hash(unix_time + "", "MD5");
    }

    public static String generatePassword(String password, String salt) {
        String merged_pass = password + "{" + salt + "}";
        return hash(merged_pass, "SHA-512");
    }

    public static String toTitleCase(String givenString) {
        givenString = givenString.trim();
        String[] arr = givenString.split("\\s+"); //collapse multiple spaces

        if(givenString.length() == 0 || arr.length == 0) //abort trivial strings
            return "";

        StringBuffer sb = new StringBuffer();
        for (int i = 0; i < arr.length; i++) {
            String s = arr[i];
            sb.append(Character.toUpperCase(s.charAt(0)));
            if(s.length() > 1)
                sb.append(s.substring(1).toLowerCase());
            sb.append(" ");
        }          
        return sb.toString().trim(); //remove last space
    }

    public static String dateToString(Date d) {
        TimeZone tz = TimeZone.getTimeZone("UTC");
        DateFormat df = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
        df.setTimeZone(tz);

        if(d == null)
            d = new Date();

        return df.format(d);
    }

    public static Date dateFromString(String s) {
        Date date = new Date();
        try {
            date = new SimpleDateFormat("MMMM d, yyyy", Locale.ENGLISH).parse(s);
        } catch(Exception e) { e.printStackTrace(); }
        return date;
    }

    private static void user_update(PrintWriter out, String user, String field, String value) {
        out.printf(UPDATE_TEMPLATE, field, field, field, '"'+value+'"', field, user);
    }

    public static void main(String[] args) throws IOException {
        

        //--------------- Process args -----------------
        if(args.length < 3) {
            System.out.println("Usage: java AttendEvent event_id input_file output_file");
            return;
        }

        USERS_FILE = args[0];
        OUTPUT_SQL_FILE = args[1];

        int event_id = 0;
        try { 
            event_id = Integer.parseInt(args[2]);
        } catch(Exception e) {
            System.out.println("event_id must be an integer: " + args[0] );
            return;            
        }


        //---------------- Setup input & output files --------------
        CSVReader reader = new CSVReader(new FileReader(USERS_FILE));

        File sqlFile = new File(OUTPUT_SQL_FILE);
 
        // if file doesnt exists, then create it
        if (!sqlFile.exists()) {
            sqlFile.createNewFile();
        }

        PrintWriter sql_bw = new PrintWriter(new BufferedWriter(new FileWriter(sqlFile.getAbsoluteFile())));
        sql_bw.println("use campsite;\n");
        sql_bw.printf(CUSTOM_QUESTION_TEMPLATE, event_id, Q_1_PROMPT, Q_1_TYPE);
        sql_bw.printf(CUSTOM_QUESTION_TEMPLATE, event_id, Q_2_PROMPT, Q_2_TYPE);
        sql_bw.println();

        String [] nextLine = reader.readNext();

        //Save headers so we can look up values by header
        INPUT_HEADERS = new ArrayList<String>(Arrays.asList(nextLine));
        

        //----------------- Process Data -----------------------------
        while ((nextLine = reader.readNext()) != null) {
            String firstname = toTitleCase( getCell(nextLine, COL_FIRST_NAME).replaceAll("\"","\'") );
            String lastname = toTitleCase( getCell(nextLine, COL_LAST_NAME).replaceAll("\"","\'") );
            String name = firstname + ' ' + lastname;
            String email = getCell(nextLine, COL_EMAIL).trim();

            String address1 = toTitleCase(getCell(nextLine, COL_HOME_ADDR_1));
            String address2 = toTitleCase(getCell(nextLine, COL_HOME_ADDR_2));
            String city = toTitleCase(getCell(nextLine, COL_HOME_CITY));
            String state = toTitleCase(getCell(nextLine, COL_HOME_STATE));
            String zip = getCell(nextLine, COL_HOME_ZIP).trim();
            String country = getCell(nextLine, COL_HOME_COUNTRY).trim().toUpperCase();
            String ip_loc = getCell(nextLine, COL_IP_LOC); //use as backup if user fields missing
            String address = address1.equals("") ? ip_loc : 
                toTitleCase(address1 + " " + address2) + ", " +
                city + ", " + state + " " + zip +
                (country.equals("US") ? "" : ", " + country);
           
            String gender = getCell(nextLine, COL_GENDER).trim().toLowerCase();
            String birthday = getCell(nextLine, COL_BIRTHDAY).trim();
            String phone_number = getCell(nextLine, COL_CELL_PHONE).trim();
            String work_phone_number = getCell(nextLine, COL_WORK_PHONE).trim();
            String organization = getCell(nextLine, COL_COMPANY).trim();
            String title = getCell(nextLine, COL_JOB_TITLE).trim();
            String website = getCell(nextLine, COL_WEBSITE).trim();
            String blog = getCell(nextLine, COL_BLOG).trim();

            String order_num = getCell(nextLine, COL_ORDER_NUM);

            String salt = generateSalt();
            String pass = email.substring(0, Math.min(email.length(), 4)) + order_num.substring(0, Math.min(email.length(), 4));
            String hashed_password = generatePassword(pass, salt); // password is email address

            //Output email to password mapping
            System.out.println(email + "\t" + pass);

            String rsvp_date = getCell(nextLine, COL_ORDER_DATE).trim();
            if(!rsvp_date.equals(""))
                rsvp_date = dateToString(dateFromString(rsvp_date));

            String external_event_id = getCell(nextLine, COL_EVENT_ID).trim();
            String ticket_type = getCell(nextLine, COL_TICKET_TYPE).trim();
            String promo_code = getCell(nextLine, COL_PROMO_CODE).trim();
            String amount_paid = getCell(nextLine, COL_TOTAL_PAID).trim();

            String q1_answer = getCell(nextLine, Q_1_PROMPT).trim().replaceAll("\"","\'");
            String q2_answer = getCell(nextLine, Q_2_PROMPT).trim().replaceAll("\"","\'");
             
            sql_bw.printf(INSERT_TEMPLATE, email, email);
            user_update(sql_bw, email, "roles", "a:0:{}");
            user_update(sql_bw, email, "username", email);
            user_update(sql_bw, email, "email", email);
            user_update(sql_bw, email, "professionalEmail", email);
            user_update(sql_bw, email, "enabled", "1");

            user_update(sql_bw, email, "salt", salt);
            user_update(sql_bw, email, "password", hashed_password);

            user_update(sql_bw, email, "uuid", UUID.randomUUID().toString());
            sql_bw.printf("UPDATE fos_user SET `created` = IF(created IS NULL, now(), created) WHERE email_canonical = '%s';\n", email);
            sql_bw.printf("UPDATE fos_user SET `updated` = IF(updated IS NULL, now(), updated) WHERE email_canonical = '%s';\n", email);
            
            user_update(sql_bw, email, "firstname", firstname);
            if(!lastname.equals("")) 
                user_update(sql_bw, email, "lastname", lastname);
            user_update(sql_bw, email, "name", firstname + ' ' + lastname);

            if(!gender.equals("")) 
                user_update(sql_bw, email, "gender", gender);

            if(!birthday.equals("")) 
                user_update(sql_bw, email, "birthdate", birthday);

            if(!phone_number.equals("")) 
                user_update(sql_bw, email, "phone_number", phone_number);

            if(!work_phone_number.equals("")) 
                user_update(sql_bw, email, "work_phone_number", work_phone_number);

            if(!organization.equals(""))
                user_update(sql_bw, email, "organization", organization);
            
            if(!title.equals(""))
                user_update(sql_bw, email, "title", title);
            
            if(!website.equals(""))
                user_update(sql_bw, email, "website", website);
            
            if(!blog.equals(""))
                user_update(sql_bw, email, "blog", blog);

            if(address1.equals("")) {
                user_update(sql_bw, email, "mailingAddress", ip_loc);
            } else {
                user_update(sql_bw, email, "mailingAddress", address);
                if(!address2.equals(""))
                    user_update(sql_bw, email, "mailingAddress2", address2);
                if(!city.equals(""))
                    user_update(sql_bw, email, "city", city);
                if(!state.equals(""))
                    user_update(sql_bw, email, "state", state);
                if(!zip.equals(""))
                    user_update(sql_bw, email, "zip", zip);
                if(!country.equals(""))
                    user_update(sql_bw, email, "country", country);
            }

            sql_bw.printf(ATTEND_TEMPLATE, 
                event_id,
                email,
                event_id,
                email,
                event_id,
                email,
                rsvp_date,
                external_event_id,
                ticket_type,
                promo_code,
                amount_paid
            );

            if(!q1_answer.equals(""))
                sql_bw.printf(CUSTOM_ANSWER_TEMPLATE, event_id, Q_1_PROMPT, email, q1_answer);

            if(!q2_answer.equals(""))
                sql_bw.printf(CUSTOM_ANSWER_TEMPLATE, event_id, Q_2_PROMPT, email, q2_answer);

            sql_bw.println();
        }

        sql_bw.printf(UPDATE_ATTENDEES_TEMPLATE, event_id, event_id);

        //Finally clean up
        sql_bw.close();
    }

}
