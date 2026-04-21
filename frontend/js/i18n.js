// κρατάει ολες τις μεταφράσεις και παρέχει βοηθητικές συναρτήσεις για την εφαρμογή τους στη σελίδα, καθώς και για τη διαχείριση της τρέχουσας γλώσσας και των switchers γλώσσας.
(function () {
    const STORAGE_KEY = "unisupport-language";

    const translations = {
        en: {
            "common.show_menu": "Show menu",
            "common.hide_menu": "Hide menu",
            "common.notifications": "Notifications",
            "common.delete_all_read": "Delete all read",
            "common.no_notifications": "No notifications yet.",
            "common.delete_notification": "Delete notification",
            "common.delete": "Delete",
            "notifications.post_approved": "Your post \"{title}\" has been approved",
            "notifications.post_rejected": "Your post \"{title}\" was rejected",
            "notifications.delete_approved": "Your delete request for \"{title}\" was approved",
            "notifications.delete_rejected": "Your delete request for \"{title}\" was rejected",
            "notifications.report_approved": "Your report for \"{title}\" was approved. Action was taken.",
            "notifications.report_rejected": "Your report for \"{title}\" was rejected.",
            "notifications.category_request_approved": "Your category request \"{name}\" was approved",
            "notifications.category_request_rejected": "Your category request \"{name}\" was rejected",
            "notifications.comment": "{user} commented on your post",
            "notifications.new_post_following": "{author} created a new post: {title}",
            "notifications.new_post_interest": "New post in your interest category ({category}): {title}",
            "notifications.admin_pending_post": "{actor} submitted a new pending post: {title}",
            "notifications.admin_post_delete_request": "{actor} submitted a post delete request for: {title}",
            "notifications.admin_post_report": "{actor} reported a post: {title}",
            "notifications.admin_comment_delete_request": "{actor} submitted a comment delete request.",
            "notifications.admin_category_request": "{actor} submitted a category request: {name}",
            "notifications.untitled_post": "Untitled post",
            "notifications.unnamed_category": "Unnamed category",
            "notifications.a_user": "A user",
            "posts.no_interests_selected": "You have not selected any interests yet.",
            "posts.showing_all_posts": "Showing all posts.",
            "posts.not_following_anyone": "You are not following anyone yet.",
            "posts.follow_users_hint": "Follow users to see their posts here.",
            "posts.no_followers_yet": "No one is following you yet.",
            "posts.followers_will_appear": "Your followers will appear here.",
            "posts.click_to_unfollow": "Click to unfollow",
            "posts.unfollow_short": "Unfollow",
            "common.cancel": "Cancel",
            "common.accept": "Accept",
            "common.close": "Close",
            "common.all": "All",
            "common.search": "Search",
            "common.clear": "Clear",
            "common.filter_search": "Toggle search filters",
            "common.search_from_date": "Search from date",
            "common.search_to_date": "Search to date",
            "common.language_switcher": "Language switcher",
            "common.all_categories": "All categories",
            "common.selected_users": "Selected users",
            "common.newest_first": "Newest first",
            "common.oldest_first": "Oldest first",
            "common.title_asc": "Title A-Z",
            "common.title_desc": "Title Z-A",
            "posts.student_workspace": "Student workspace",
            "posts.signed_in_as": "Signed in as",
            "posts.create_post": "Create Post",
            "posts.posts": "Posts",
            "posts.followers": "Followers",
            "posts.pending_posts": "Pending Posts",
            "posts.pending_delete_requests": "Pending Delete Requests",
            "posts.reports": "Reports",
            "posts.token_history": "Token history",
            "posts.posts_feed": "Posts Feed",
            "posts.following": "Following",
            "posts.search_results": "Search Results",
            "posts.view_edit_profile": "View & Edit profile",
            "posts.edit_interests": "Edit interests",
            "posts.request_category": "Request category",
            "posts.watch_ads": "Watch Ads",
            "posts.tokens": "Tokens",
            "posts.free_daily_title": "Free daily download available",
            "posts.free_daily_desc": "You still have one free daily download available today.",
            "posts.search_placeholder": "Search posts by keyword",
            "posts.all_followers": "All followers",
            "posts.followers_short": "Followers",
            "posts.about_title": "About UniSupport",
            "posts.about_text": "UniSupport is a student support platform for staying organized, sharing knowledge, and connecting with others in one place.",
            "posts.project_info": "Project Information",
            "posts.project_info_text": "This system was developed by Pelagia Koniotaki, Antriani Theofanous, Panteleimoni Alexandrou, Paraskevas Vafeiadis and Panagiotis Panagiwtou, third-year students of the Department of Electrical Engineering, Computer Engineering and Informatics at the Cyprus University of Technology, under the supervision of Professor Andreas S. Andreou, as part of the course 'Software Technology Project and Professional Practice'.",
            "posts.project_info_location": "Limassol, May 2026",
            "posts.delete_rejected_post": "Delete Rejected Post",
            "posts.delete_rejected_post_desc": "This will permanently remove the rejected post from your history. This action cannot be undone.",
            "posts.delete_permanently": "Delete permanently",
            "posts.confirm_publication": "Confirm Publication",
            "posts.confirm_publication_desc": "After publishing, this post cannot be deleted directly and requires a delete request.",
            "posts.info_button_label": "Open project information",
            "posts.create_post_heading": "Create New Post",
            "posts.post_title": "Post title",
            "posts.category_label": "Category",
            "posts.write_content": "Write your content...",
            "posts.select_category": "Select Category",
            "posts.publish_anonymously": "Publish anonymously",
            "posts.anonymous_hint": "Your name will be hidden for users. Admins can still view the post owner.",
            "posts.attachments": "Attachments",
            "posts.attachments_hint": "At least 1 file required, up to 5 files (jpg, png, pdf, doc, docx, txt, zip)",
            "posts.choose_files": "Choose Files",
            "posts.publish": "Publish",
            "posts.current_token_balance": "Current token balance",
            "posts.token_history_heading": "Token History",
            "posts.token_history_desc": "See where you earned tokens and where you spent them.",
            "posts.no_token_transactions": "No token transactions found yet.",
            "posts.earned": "Earned",
            "posts.spent": "Spent",
            "posts.type": "Type",
            "posts.amount": "Amount",
            "posts.date": "Date",
            "posts.no_transactions_filter": "No transactions in this category yet.",
            "posts.no_posts_available": "No posts available yet.",
            "posts.no_posts_found": "No posts found.",
            "posts.failed_search_results": "Failed to load search results.",
            "posts.your_interests": "Your interests",
            "posts.following_you": "Following you",
            "posts.follow_action": "+ Follow",
            "posts.follow_title": "Click to follow user",
            "posts.anonymous": "Anonymous",
            "posts.unknown_date": "Unknown date",
            "posts.confirm_unfollow_title": "Confirm Unfollow",
            "posts.confirm_unfollow_desc": "Are you sure you want to unfollow this user?",
            "posts.unfollow": "Unfollow",
            "posts.no_delete_requests": "No delete requests found.",
            "posts.no_reports": "No reports found.",
            "posts.visible": "Visible",
            "posts.removed": "Removed",
            "login.subtitle": "Sign in to access your student workspace, stay organized, and keep up with the latest activity.",
            "login.title": "Login",
            "login.username": "Username",
            "login.password": "Password",
            "login.no_account": "No account?",
            "login.register": "Register",
            "login.forgot_password": "Forgot password?",
            "login.submit": "Login",
            "admin.moderation_workspace": "Moderation workspace",
            "admin.signed_in_as": "Signed in as",
            "admin.posts": "Posts",
            "admin.published": "Published",
            "admin.pending_posts": "Pending Posts",
            "admin.post_delete_requests": "Post Delete Requests",
            "admin.comment_delete_requests": "Comment Delete Requests",
            "admin.category_requests": "Category Requests",
            "admin.reports": "Reports",
            "admin.posts_title": "Admin Posts",
            "admin.pending_title": "Pending Posts",
            "admin.delete_requests_title": "Post Delete Requests",
            "admin.comment_delete_title": "Comment Delete Requests",
            "admin.category_requests_title": "Category Requests",
            "admin.reports_title": "Reports",
            "admin.view_profile": "View profile",
            "admin.search_placeholder": "Search by title, category, or author",
            "admin.all_users": "All users",
            "admin.users": "Users",
            "admin.pending": "Pending",
            "admin.approved": "Approved",
            "admin.rejected": "Rejected",
            "admin.panel_pending_desc": "Review submitted posts and decide whether they should be published.",
            "admin.panel_delete_desc": "Review user deletion requests and decide whether the related posts should be removed.",
            "admin.panel_comment_delete_desc": "Review user requests to remove comments and decide whether they should be deleted.",
            "admin.panel_category_desc": "Review user suggestions for new categories and choose whether to create them.",
            "admin.panel_reports_desc": "Review reported posts and decide whether the post should be removed.",
            "admin.profile_title": "Admin Profile",
            "admin.profile_desc": "Account details for the current administrator.",
            "admin.username": "Username",
            "admin.email": "Email",
            "admin.toggle_search_filters": "Toggle search filters",
            "admin.pending_category_requests": "Pending Category Requests",
            "admin.reject_post": "Reject Post",
            "admin.reject_post_desc": "Please explain why this post is being rejected.",
            "admin.reject_reason_placeholder": "Write your reason...",
            "admin.submit_rejection": "Submit rejection",
            "admin.no_items": "No items found."
            ,"admin.no_posts_for_status": "No posts found for this status."
            ,"admin.no_pending_category_requests": "No pending category requests."
            ,"admin.failed_search_results": "Failed to load search results."
            ,"admin.rejection_reason_required": "Please enter a rejection reason."
        },
        el: {
            "common.show_menu": "Εμφάνιση μενού",
            "common.hide_menu": "Απόκρυψη μενού",
            "common.notifications": "Ειδοποιήσεις",
            "common.delete_all_read": "Διαγραφή αναγνωσμένων",
            "common.no_notifications": "Δεν υπάρχουν ειδοποιήσεις ακόμη.",
            "common.delete_notification": "Διαγραφή ειδοποίησης",
            "common.delete": "Διαγραφή",
            "notifications.post_approved": "Η ανάρτησή σας «{title}» εγκρίθηκε",
            "notifications.post_rejected": "Η ανάρτησή σας «{title}» απορρίφθηκε",
            "notifications.delete_approved": "Το αίτημα διαγραφής σας για την ανάρτηση «{title}» εγκρίθηκε",
            "notifications.delete_rejected": "Το αίτημα διαγραφής σας για την ανάρτηση «{title}» απορρίφθηκε",
            "notifications.report_approved": "Η αναφορά σας για την ανάρτηση «{title}» εγκρίθηκε. Ελήφθησαν μέτρα.",
            "notifications.report_rejected": "Η αναφορά σας για την ανάρτηση «{title}» απορρίφθηκε.",
            "notifications.category_request_approved": "Το αίτημα κατηγορίας «{name}» εγκρίθηκε",
            "notifications.category_request_rejected": "Το αίτημα κατηγορίας «{name}» απορρίφθηκε",
            "notifications.comment": "Ο/Η {user} σχολίασε στην ανάρτησή σας",
            "notifications.new_post_following": "Ο/Η {author} δημοσίευσε νέα ανάρτηση: {title}",
            "notifications.new_post_interest": "Νέα ανάρτηση στην κατηγορία ενδιαφέροντός σας ({category}): {title}",
            "notifications.admin_pending_post": "Ο/Η {actor} υπέβαλε νέα εκκρεμή ανάρτηση: {title}",
            "notifications.admin_post_delete_request": "Ο/Η {actor} υπέβαλε αίτημα διαγραφής για την ανάρτηση: {title}",
            "notifications.admin_post_report": "Ο/Η {actor} ανέφερε ανάρτηση: {title}",
            "notifications.admin_comment_delete_request": "Ο/Η {actor} υπέβαλε αίτημα διαγραφής σχολίου.",
            "notifications.admin_category_request": "Ο/Η {actor} υπέβαλε αίτημα κατηγορίας: {name}",
            "notifications.untitled_post": "Ανάρτηση χωρίς τίτλο",
            "notifications.unnamed_category": "Κατηγορία χωρίς όνομα",
            "notifications.a_user": "Ένας χρήστης",
            "posts.no_interests_selected": "Δεν έχεις επιλέξει ακόμη ενδιαφέροντα.",
            "posts.showing_all_posts": "Εμφάνιση όλων των αναρτήσεων.",
            "posts.not_following_anyone": "Δεν ακολουθείς κανέναν ακόμη.",
            "posts.follow_users_hint": "Ακολούθησε χρήστες για να βλέπεις τις αναρτήσεις τους εδώ.",
            "posts.no_followers_yet": "Δεν σε ακολουθεί κανείς ακόμη.",
            "posts.followers_will_appear": "Οι ακόλουθοί σου θα εμφανιστούν εδώ.",
            "posts.click_to_unfollow": "Πάτησε για να καταργήσεις την ακολούθηση",
            "posts.unfollow_short": "Κατάργηση",
            "common.cancel": "Ακύρωση",
            "common.accept": "Αποδοχή",
            "common.close": "Κλείσιμο",
            "common.all": "Όλα",
            "common.search": "Αναζήτηση",
            "common.clear": "Καθαρισμός",
            "common.filter_search": "Εναλλαγή φίλτρων αναζήτησης",
            "common.search_from_date": "Αναζήτηση από ημερομηνία",
            "common.search_to_date": "Αναζήτηση έως ημερομηνία",
            "common.language_switcher": "Επιλογή γλώσσας",
            "common.all_categories": "Όλες οι κατηγορίες",
            "common.selected_users": "Επιλεγμένοι χρήστες",
            "common.newest_first": "Νεότερα πρώτα",
            "common.oldest_first": "Παλαιότερα πρώτα",
            "common.title_asc": "Τίτλος Α-Ω",
            "common.title_desc": "Τίτλος Ω-Α",
            "posts.student_workspace": "Χώρος φοιτητή",
            "posts.signed_in_as": "Συνδεδεμένος ως",
            "posts.create_post": "Ανάρτησε",
            "posts.posts": "Υλικό",
            "posts.followers": "Ακόλουθοι",
            "posts.pending_posts": "Αναρτήσεις",
            "posts.pending_delete_requests": "Διαγραφές",
            "posts.reports": "Αναφορές",
            "posts.token_history": "Tokens",
            "posts.posts_feed": "Υλικό",
            "posts.following": "Ακολουθώ",
            "posts.search_results": "Αποτελέσματα αναζήτησης",
            "posts.view_edit_profile": "Προβολή & επεξεργασία προφίλ",
            "posts.edit_interests": "Επεξεργασία ενδιαφερόντων",
            "posts.request_category": "Αίτημα κατηγορίας",
            "posts.watch_ads": "Παρακολούθηση διαφημίσεων",
            "posts.tokens": "Tokens",
            "posts.free_daily_title": "Διαθέσιμη δωρεάν ημερήσια λήψη",
            "posts.free_daily_desc": "Έχετε ακόμη μία δωρεάν ημερήσια λήψη διαθέσιμη σήμερα.",
            "posts.search_placeholder": "Αναζήτηση αναρτήσεων με λέξη-κλειδί",
            "posts.all_followers": "Όλοι οι ακόλουθοι",
            "posts.followers_short": "Ακόλουθοι",
            "posts.about_title": "Σχετικά με το UniSupport",
            "posts.about_text": "Το UniSupport είναι μια πλατφόρμα υποστήριξης φοιτητών για οργάνωση, διαμοιρασμό γνώσης και επικοινωνία σε ένα μέρος.",
            "posts.project_info": "Πληροφορίες έργου",
            "posts.project_info_text": "Το σύστημα αυτό αναπτύχθηκε από την Πελαγία Κωνιωτάκη, την Αντριανή Θεοφάνους, την Παντελεήμωνη Αλεξάνδρου, τον Παρασκευά Βαφειάδη και τον Παναγίωτη Παναγίωτου, φοιτητές τρίτου έτους του Τμήματος Ηλεκτρολόγων Μηχανικών, Μηχανικών Υπολογιστών και Πληροφορικής του Τεχνολογικού Πανεπιστημίου Κύπρου, υπό την επίβλεψη του Καθηγητή Αντρέα Ανδρέου , στο πλαίσιο του μαθήματος 'Software Technology Project and Professional Practice'.",
            "posts.project_info_location": "Λεμεσός, Μάιος 2026",
            "posts.delete_rejected_post": "Διαγραφή απορριφθείσας ανάρτησης",
            "posts.delete_rejected_post_desc": "Αυτό θα αφαιρέσει οριστικά την απορριφθείσα ανάρτηση από το ιστορικό σας. Η ενέργεια δεν αναιρείται.",
            "posts.delete_permanently": "Οριστική διαγραφή",
            "posts.confirm_publication": "Επιβεβαίωση δημοσίευσης",
            "posts.confirm_publication_desc": "Μετά τη δημοσίευση, η ανάρτηση δεν μπορεί να διαγραφεί απευθείας και απαιτεί αίτημα διαγραφής.",
            "posts.info_button_label": "Άνοιγμα πληροφοριών έργου",
            "posts.create_post_heading": "Ανάρτησε",
            "posts.post_title": "Τίτλος ανάρτησης",
            "posts.category_label": "Κατηγορία",
            "posts.write_content": "Γράψτε το περιεχόμενό σας...",
            "posts.select_category": "Επιλογή κατηγορίας",
            "posts.publish_anonymously": "Δημοσίευση ανώνυμα",
            "posts.anonymous_hint": "Το όνομά σας θα είναι κρυμμένο για τους χρήστες. Οι διαχειριστές μπορούν ακόμη να δουν τον ιδιοκτήτη της ανάρτησης.",
            "posts.attachments": "Συνημμένα",
            "posts.attachments_hint": "Απαιτείται τουλάχιστον 1 αρχείο, έως 5 αρχεία (jpg, png, pdf, doc, docx, txt, zip)",
            "posts.choose_files": "Επιλογή αρχείων",
            "posts.publish": "Δημοσίευση",
            "posts.current_token_balance": "Τρέχον υπόλοιπο token",
            "posts.token_history_heading": "Tokens",
            "posts.token_history_desc": "Δείτε πού κερδίσατε token και πού τα ξοδέψατε.",
            "posts.no_token_transactions": "Δεν υπάρχουν ακόμη κινήσεις token.",
            "posts.earned": "Κερδισμένα",
            "posts.spent": "Ξοδεμένα",
            "posts.type": "Τύπος",
            "posts.amount": "Ποσό",
            "posts.date": "Ημερομηνία",
            "posts.no_transactions_filter": "Δεν υπάρχουν κινήσεις σε αυτή την κατηγορία.",
            "posts.no_posts_available": "Δεν υπάρχουν αναρτήσεις ακόμη.",
            "posts.no_posts_found": "Δεν βρέθηκαν αναρτήσεις.",
            "posts.failed_search_results": "Αποτυχία φόρτωσης αποτελεσμάτων αναζήτησης.",
            "posts.your_interests": "Τα ενδιαφέροντά σου",
            "posts.following_you": "Σε ακολουθούν",
            "posts.follow_action": "+ Ακολούθηση",
            "posts.follow_title": "Πάτησε για να ακολουθήσεις τον χρήστη",
            "posts.anonymous": "Ανώνυμος",
            "posts.unknown_date": "Άγνωστη ημερομηνία",
            "posts.confirm_unfollow_title": "Επιβεβαίωση κατάργησης ακολούθησης",
            "posts.confirm_unfollow_desc": "Είσαι σίγουρος ότι θέλεις να καταργήσεις την ακολούθηση αυτού του χρήστη;",
            "posts.unfollow": "Κατάργηση ακολούθησης",
            "posts.no_delete_requests": "Δεν βρέθηκαν αιτήματα διαγραφής.",
            "posts.no_reports": "Δεν βρέθηκαν αναφορές.",
            "posts.visible": "Ορατό",
            "posts.removed": "Αφαιρέθηκε",
            "login.subtitle": "Συνδεθείτε για να αποκτήσετε πρόσβαση στον φοιτητικό σας χώρο, να μείνετε οργανωμένοι και να βλέπετε τη νεότερη δραστηριότητα.",
            "login.title": "Σύνδεση",
            "login.username": "Όνομα χρήστη",
            "login.password": "Κωδικός πρόσβασης",
            "login.no_account": "Δεν έχετε λογαριασμό;",
            "login.register": "Εγγραφή",
            "login.forgot_password": "Ξεχάσατε τον κωδικό;",
            "login.submit": "Σύνδεση",
            "admin.moderation_workspace": "Χώρος διαχείρισης",
            "admin.signed_in_as": "Συνδεδεμένος ως",
            "admin.posts": "Αναρτήσεις",
            "admin.published": "Δημοσιευμένο",
            "admin.pending_posts": "Εκκρεμείς αναρτήσεις",
            "admin.post_delete_requests": "Αιτήματα διαγραφής αναρτήσεων",
            "admin.comment_delete_requests": "Αιτήματα διαγραφής σχολίων",
            "admin.category_requests": "Αιτήματα κατηγοριών",
            "admin.reports": "Αναφορές",
            "admin.posts_title": "Αναρτήσεις διαχειριστή",
            "admin.pending_title": "Εκκρεμείς αναρτήσεις",
            "admin.delete_requests_title": "Αιτήματα διαγραφής αναρτήσεων",
            "admin.comment_delete_title": "Αιτήματα διαγραφής σχολίων",
            "admin.category_requests_title": "Αιτήματα κατηγοριών",
            "admin.reports_title": "Αναφορές",
            "admin.view_profile": "Προβολή προφίλ",
            "admin.search_placeholder": "Αναζήτηση με τίτλο, κατηγορία ή χρήστη",
            "admin.all_users": "Όλοι οι χρήστες",
            "admin.users": "Χρήστες",
            "admin.pending": "Εκκρεμεί",
            "admin.approved": "Εγκεκριμένο",
            "admin.rejected": "Απορρίφθηκε",
            "admin.panel_pending_desc": "Ελέγξτε τις υποβληθείσες αναρτήσεις και αποφασίστε αν θα δημοσιευθούν.",
            "admin.panel_delete_desc": "Ελέγξτε τα αιτήματα διαγραφής και αποφασίστε αν οι σχετικές αναρτήσεις πρέπει να αφαιρεθούν.",
            "admin.panel_comment_delete_desc": "Ελέγξτε τα αιτήματα αφαίρεσης σχολίων και αποφασίστε αν πρέπει να διαγραφούν.",
            "admin.panel_category_desc": "Ελέγξτε τις προτάσεις χρηστών για νέες κατηγορίες και επιλέξτε αν θα δημιουργηθούν.",
            "admin.panel_reports_desc": "Ελέγξτε τις αναφερόμενες αναρτήσεις και αποφασίστε αν πρέπει να αφαιρεθούν.",
            "admin.profile_title": "Προφίλ διαχειριστή",
            "admin.profile_desc": "Στοιχεία λογαριασμού του τρέχοντος διαχειριστή.",
            "admin.username": "Όνομα χρήστη",
            "admin.email": "Email",
            "admin.toggle_search_filters": "Εναλλαγή φίλτρων αναζήτησης",
            "admin.pending_category_requests": "Εκκρεμή αιτήματα κατηγοριών",
            "admin.reject_post": "Απόρριψη ανάρτησης",
            "admin.reject_post_desc": "Παρακαλώ εξηγήστε γιατί αυτή η ανάρτηση απορρίπτεται.",
            "admin.reject_reason_placeholder": "Γράψτε τον λόγο σας...",
            "admin.submit_rejection": "Υποβολή απόρριψης",
            "admin.no_items": "Δεν βρέθηκαν στοιχεία."
            ,"admin.no_posts_for_status": "Δεν βρέθηκαν αναρτήσεις για αυτή την κατάσταση."
            ,"admin.no_pending_category_requests": "Δεν υπάρχουν εκκρεμή αιτήματα κατηγοριών."
            ,"admin.failed_search_results": "Αποτυχία φόρτωσης αποτελεσμάτων αναζήτησης."
            ,"admin.rejection_reason_required": "Παρακαλώ συμπληρώστε λόγο απόρριψης."
        }
    };

    function getLanguage() {
        const stored = window.localStorage.getItem(STORAGE_KEY);
        return stored === "el" ? "el" : "en";
    }

    function setLanguage(nextLanguage) {
        const language = nextLanguage === "el" ? "el" : "en";
        window.localStorage.setItem(STORAGE_KEY, language);
        document.documentElement.lang = language;
        applyTranslations(document);
        window.dispatchEvent(new CustomEvent("unisupport:languagechange", { detail: { language } }));
    }

    function t(key, fallback = "") {
        const language = getLanguage();
        return translations[language]?.[key] ?? translations.en[key] ?? fallback;
    }

    function formatString(template, params) {
        if (!params || typeof template !== "string") {
            return template;
        }
        return template.replace(/\{(\w+)\}/g, (match, name) => {
            if (Object.prototype.hasOwnProperty.call(params, name)) {
                return String(params[name] ?? "");
            }
            return match;
        });
    }

    function tf(key, params, fallback = "") {
        const template = t(key, fallback);
        return formatString(template, params);
    }

    function applyTranslations(root = document) {
        root.querySelectorAll("[data-i18n]").forEach((node) => {
            node.textContent = t(node.dataset.i18n, node.textContent);
        });

        root.querySelectorAll("[data-i18n-placeholder]").forEach((node) => {
            node.setAttribute("placeholder", t(node.dataset.i18nPlaceholder, node.getAttribute("placeholder") || ""));
        });

        root.querySelectorAll("[data-i18n-aria-label]").forEach((node) => {
            node.setAttribute("aria-label", t(node.dataset.i18nAriaLabel, node.getAttribute("aria-label") || ""));
        });

        root.querySelectorAll("[data-i18n-title]").forEach((node) => {
            node.setAttribute("title", t(node.dataset.i18nTitle, node.getAttribute("title") || ""));
        });
    }

    function syncSwitchers() {
        const language = getLanguage();
        document.querySelectorAll("[data-language-switcher]").forEach((switcher) => {
            const buttons = switcher.querySelectorAll("[data-language]");
            buttons.forEach((button) => {
                const isActive = button.getAttribute("data-language") === language;
                button.classList.toggle("is-active", isActive);
                button.setAttribute("aria-pressed", isActive ? "true" : "false");
            });
        });
    }

    function initSwitchers() {
        document.querySelectorAll("[data-language-switcher]").forEach((switcher) => {
            switcher.addEventListener("click", (event) => {
                const button = event.target.closest("[data-language]");
                if (!button) {
                    return;
                }
                setLanguage(button.getAttribute("data-language") || "en");
                syncSwitchers();
            });
        });
        syncSwitchers();
    }

    document.addEventListener("DOMContentLoaded", () => {
        document.documentElement.lang = getLanguage();
        applyTranslations(document);
        initSwitchers();
    });

    window.UniSupportI18n = {
        t,
        tf,
        format: formatString,
        getLanguage,
        setLanguage,
        applyTranslations,
        syncSwitchers
    };
})();
