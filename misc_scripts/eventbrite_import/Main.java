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

class Main {
        
    public static final String COL_EVENT_NAME = "Event Name";
    public static final String COL_EVENT_ID = "Event ID";
    public static final String COL_ORG_NAME = "Organizer Name";
    public static final String COL_NUM_ATTENDEES = "Attendee #";
    public static final String COL_1 = "Barcode #";
    public static final String COL_2 = "Order Date";
    public static final String COL_BUYER_FIRST_NAME = "Buyer Last Name ";
    public static final String COL_BUYER_LAST_NAME = "Buyer First Name";
    public static final String COL_BUYER_EMAIL = "Buyer Email ";
    public static final String COL_PREFIX = "Prefix";
    public static final String COL_LAST_NAME = "Last Name";
    public static final String COL_FIRST_NAME = "First Name";
    public static final String COL_SUFFIX = "Suffix";
    public static final String COL_EMAIL = "Email";
    public static final String COL_11 = "Quantity";
    public static final String COL_12 = "Ticket Type";
    public static final String COL_13 = "Date Attending";
    public static final String COL_14 = "Device #";
    public static final String COL_15 = "Check-In Date";
    public static final String COL_IP_LOC = "IP Location";
    public static final String COL_17 = "Discount";
    public static final String COL_18 = "Group";
    public static final String COL_19 = "Affiliate";
    public static final String COL_20 = "Order #";
    public static final String COL_21 = "Order Type";
    public static final String COL_22 = "Currency";
    public static final String COL_23 = "Total Paid";
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
    public static final String COL_BIRTHDAY = "Age Birth Date";
    public static final String COL_40 = "Please suggest a topic for an unconference session";
    public static final String COL_41 = "Would you like to receive BigData-related emails from our event sponsors.";
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

    private static ArrayList<String> INPUT_HEADERS;

    private static final String USERS_FILE="full_data.csv";
    private static final String OUTPUT_SQL_FILE="import_users.sql";

    private static final String INSERT_TEMPLATE = 
    "INSERT INTO fos_user (" + 
        "`roles`," + 
        "`name`," + 
        "`username`," + "`username_canonical`," + "`email`," +  "`email_canonical`," + 
        //"`industry`," + 
        //"`professionalEmail`," + 
        //"`linkedIn`," + 
        "`mailingAddress`," + 
        //"`title`," + 
        "`organization`," + 
        "`website`," + 
        "`enabled`," + 
        "`salt`," +
        "`password`," +
        "`created`," +
        "`updated`," + 
        "`uuid`" +
    ") VALUES (\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",now(),now(),\"%s\");\n";

    private static final String ATTEND_TEMPLATE = 
    "insert into group_events_attendees (" +
        "groupevent_id, " +
        "user_id" + 
    ") values ( " + 
        "%d, " +
        "(select id from fos_user where username = \"%s\") " + 
    ");\n" +

    "insert into pd_groups_members (" +
        "group_id, " + 
        "user_id" + 
    ") values ( " +
        "(select group_id from group_event where id = \"%d\"), " +
        "(select id from fos_user where username = \"%s\") " + 
    ");\n" +

    "insert into group_event_rsvp_actions (" + 
        "event_id, " + 
        "user_id, " + 
        "rsvp_at, " + 
        "updated_at, " + 
        "created_at, " + 
        "attendance" + 
    ") values ( " + 
        "%d, " + 
        "(select id from fos_user where username = \"%s\"), " +
        "now(), " + 
        "now(), " + 
        "now(), " + 
        "\"ATTENDING_YES\" " + 
    ");\n\n";

    private static final String UPDATE_ATTENDEES_TEMPLATE = 
    "update group_event set attendeeCount = " + 
        "(select count(groupevent_id) from group_events_attendees where groupevent_id = \"%d\") " + 
    "where id = \"%d\";\n\n";

    public static String getCell(String[] row_data, String column_name) {
        int column_index = INPUT_HEADERS.indexOf(column_name);
        if(column_index == -1)
            return null;

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

    public static String geenerateSalt() {
        int unix_time = (int) (System.currentTimeMillis() / 1000L);
        return hash(unix_time + "", "MD5");
    }

    public static String generatePassword(String password, String salt) {
        String merged_pass = password + "{" + salt + "}";
        return hash(merged_pass, "SHA-512");
    }

    public static String toTitleCase(String givenString) {
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

    // public static String getDateTime() {
    //     TimeZone tz = TimeZone.getTimeZone("UTC");
    //     DateFormat df = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
    //     df.setTimeZone(tz);

    //     return df.format(new Date());
    // }

    public static void main(String[] args) throws IOException {
        

        //--------------- Process args -----------------
        if(args.length < 1)
            System.out.println("Usage: java AttendEvent event_id");

        int event_id = 0;
        try { 
            event_id = Integer.parseInt(args[0]);
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

        String [] nextLine = reader.readNext();

        //Save headers so we can look up values by header
        INPUT_HEADERS = new ArrayList<String>(Arrays.asList(nextLine));
        

        //----------------- Process Data -----------------------------
        while ((nextLine = reader.readNext()) != null) {
            String name = toTitleCase( getCell(nextLine, COL_FIRST_NAME) + ' ' + getCell(nextLine, COL_LAST_NAME));
            String email = getCell(nextLine, COL_EMAIL).trim();

            String address1 = getCell(nextLine, COL_HOME_ADDR_1);
            String address2 = getCell(nextLine, COL_HOME_ADDR_2);
            String country = getCell(nextLine, COL_HOME_COUNTRY).toUpperCase();
            String ip_loc = getCell(nextLine, COL_IP_LOC); //use as backup if user fields missing

            String salt = geenerateSalt();
            String password = generatePassword(email, salt); // password is email address

            //String now_datetime = getDateTime();

            String uuid = UUID.randomUUID().toString();

            String address = address1.equals("") ? ip_loc : 
                toTitleCase(address1 + " " + address2) + ", " +
                toTitleCase(getCell(nextLine, COL_HOME_CITY)) + ", " +
                toTitleCase(getCell(nextLine, COL_HOME_STATE)) + " " + 
                getCell(nextLine, COL_HOME_ZIP) +
                (country.equals("US") ? "" : ", " + country.toUpperCase());
            
            //System.out.println(address);
            sql_bw.printf(INSERT_TEMPLATE, 
                "a:0:{}",
                name, 
                email, 
                email,
                email,
                email,
                address,
                getCell(nextLine, COL_COMPANY).trim(),
                getCell(nextLine, COL_WEBSITE).trim(),
                1,
                salt,
                password,
                //now_datetime,
                //now_datetime,
                uuid
            );

            sql_bw.printf(ATTEND_TEMPLATE, 
                event_id,
                email,
                event_id,
                email,
                event_id,
                email,
                event_id,
                event_id
            );

        }

        sql_bw.printf(UPDATE_ATTENDEES_TEMPLATE, event_id, event_id);


        //Finally clean up
        sql_bw.close();
    }

}
