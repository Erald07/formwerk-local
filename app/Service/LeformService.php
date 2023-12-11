<?php

namespace App\Service;

use DateTime;
use App\Models\Validation;
use App\Models\Record;
use App\Models\Upload;
use App\Models\Style;
use App\Service\LeformFormService;
use App\Service\NativeStylesService;

use Illuminate\Support\Facades\Storage;

class LeformService
{
    public $leform_native_styles = [];

    public static $formPropertiesMetaCustomXmlFields = [
        'values' => [["name" => "ID", "value" => ""]],
        'label' => 'Custom fields',
        'tooltip' => 'Custom fields',
        'type' => 'custom-xml-fields',
        'visible' => ['generate-xml-on-save' => ['on']],
        'validation' => '^(?!xml|Xml|xMl|xmL|XMl|xML|XmL|XML)[A-Za-z_][A-Za-z0-9-_.]*$'
    ];

    public static $propertiesMetaCustomXmlFields = [
        'values' => [["name" => "ID", "value" => ""]],
        'label' => 'Custom fields',
        'tooltip' => 'Custom fields',
        'type' => 'custom-xml-fields',
        'visible' => ['xml-field-not-exported' => ['off']],
        'validation' => '^(?!xml|Xml|xMl|xmL|XMl|xML|XmL|XML)[A-Za-z_][A-Za-z0-9-_.]*$'
    ];

    public static $defaultXmlFieldNames = [
        // 'groupTag' => 'Gruppe',
        'tag' => 'Element',
        'key' => 'Label',
        'value' => 'Eingabe',
        'value_max' => 'Maxwert',
        'value_default' => 'StandardWert',
        'type' => 'Feldtyp'
    ];

    public static $formPropertiesMetaXmlFieldNames = [
        'values' => [],
        'label' => 'Field names',
        'tooltip' => 'Field names',
        'type' => 'xml-field-names',
        'visible' => ['generate-xml-on-save' => ['on']],
        'validation' => '^(?!xml|Xml|xMl|xmL|XMl|xML|XmL|XML)[A-Za-z_][A-Za-z0-9-_.]*$'
    ];

    public static $propertiesMetaXmlFieldNames = [
        'values' => [],
        'label' => 'Field names',
        'tooltip' => 'Field names',
        'type' => 'xml-field-names',
        'visible' => ['xml-field-not-exported' => ['off']],
        'validation' => '^(?!xml|Xml|xMl|xmL|XMl|xML|XmL|XML)[A-Za-z_][A-Za-z0-9-_.]*$'
    ];

    function __construct()
    {
        $this->advancedOptions = array_merge($this->advancedOptions, [
            'label-form-values' => 'Form Values',
            'label-payment' => 'Payment',
            'label-general-info' => 'General Info',
            'label-raw-details' => 'Raw Details',
            'label-technical-info' => 'Technical Info'
        ]);

        $nativeStylesService = new NativeStylesService();
        $this->leform_native_styles = $nativeStylesService->leform_native_styles;
    }

    public $advancedOptions = [
        'enable-custom-js' => 'off',
        'enable-htmlform' => 'off',
        'enable-post' => 'off',
        'enable-mysql' => 'off',
        'enable-wpuser' => 'off',
        'enable-acellemail' => 'off',
        'enable-activecampaign' => 'off',
        'enable-activetrail' => 'off',
        'enable-agilecrm' => 'off',
        'enable-automizy' => 'off',
        'enable-avangemail' => 'off',
        'enable-authorizenet' => 'off',
        'enable-aweber' => 'off',
        'enable-birdsend' => 'off',
        'enable-bitrix24' => 'off',
        'enable-campaignmonitor' => 'off',
        'enable-cleverreach' => 'off',
        'enable-constantcontact' => 'off',
        'enable-conversio' => 'off',
        'enable-drip' => 'off',
        'enable-fluentcrm' => 'off',
        'enable-freshmail' => 'off',
        'enable-getresponse' => 'off',
        'enable-hubspot' => 'off',
        'enable-inbox' => 'off',
        'enable-jetpack' => 'off',
        'enable-klaviyo' => 'off',
        'enable-madmimi' => 'off',
        'enable-mailautic' => 'off',
        'enable-mailchimp' => 'on',
        'enable-mailerlite' => 'off',
        'enable-mailfit' => 'off',
        'enable-mailgun' => 'off',
        'enable-mailjet' => 'off',
        'enable-mailpoet' => 'off',
        'enable-mailster' => 'off',
        'enable-mailwizz' => 'off',
        'enable-mautic' => 'off',
        'enable-moosend' => 'off',
        'enable-mumara' => 'off',
        'enable-omnisend' => 'off',
        'enable-ontraport' => 'off',
        'enable-rapidmail' => 'off',
        'enable-salesautopilot' => 'off',
        'enable-sendfox' => 'off',
        'enable-sendgrid' => 'off',
        'enable-sendinblue' => 'off',
        'enable-sendpulse' => 'off',
        'enable-sendy' => 'off',
        'enable-thenewsletterplugin' => 'off',
        'enable-tribulant' => 'off',
        'enable-ymlp' => 'off',
        'enable-zapier' => 'off',
        'enable-zohocrm' => 'off',
        'enable-blockchain' => 'off',
        'enable-instamojo' => 'off',
        'enable-interkassa' => 'off',
        'enable-mollie' => 'off',
        'enable-payfast' => 'off',
        'enable-paypal' => 'off',
        'enable-paystack' => 'off',
        'enable-payumoney' => 'off',
        'enable-perfectmoney' => 'off',
        'enable-razorpay' => 'off',
        'enable-skrill' => 'off',
        'enable-stripe' => 'off',
        'enable-wepay' => 'off',
        'enable-yandexmoney' => 'off',
        'enable-bulkgate' => 'off',
        'enable-gatewayapi' => 'off',
        'enable-nexmo' => 'off',
        'enable-twilio' => 'off',
        'enable-clearout' => 'off',
        'enable-kickbox' => 'off',
        'enable-thechecker' => 'on',
        'enable-truemail' => 'off',
        'minified-sources' => 'on',
        'admin-menu-stats' => 'on',
        'admin-menu-analytics' => 'on',
        'admin-menu-transactions' => 'on',
        'important-enable' => 'off',
        'custom-fonts' => ''
    ];

    public $plugins_url = '/public';
    public $gmt_offset = 0;

    public $autocomplete_options = [
        'off' => 'None',
        'name' => 'Full Name (name)',
        'given-name' => 'First Name (given-name)',
        'additional-name' => 'Middle Name (additional-name)',
        'family-name' => 'Last Name (family-name)',
        'email' => 'Email (email)',
        'tel' => 'Phone (tel)',
        'street-address' => 'Single Address Line (street-address)',
        'address-line1' => 'Address Line 1 (address-line1)',
        'address-line2' => 'Address Line 2 (address-line2)',
        'address-level1' => 'State or Province (address-level1)',
        'address-level2' => 'City (address-level2)',
        'postal-code' => 'ZIP Code (postal-code)',
        'country' => 'Country (country)',
        'cc-name' => 'Name on Card (cc-name)',
        'cc-number' => 'Card Number (cc-number)',
        'cc-csc' => 'CVC (cc-csc)',
        'cc-exp-month' => 'Expiry (month) (cc-exp-month)',
        'cc-exp-year' => 'Expiry (year) (cc-exp-year)',
        'cc-exp' => 'Expiry (cc-exp)',
        'cc-type' => 'Card Type (cc-type)'
    ];

    private function getTranslatedAutocompleteOptions()
    {
        $translatedOptions = [];

        foreach ($this->autocomplete_options as $key => $value) {
            $translatedOptions[$key] = __($value);
        }

        return $translatedOptions;
    }

    private function getTranslatedReportOverrideOptions()
    {
        return [
            'override' => __('Override file'),
            'append' => __('Append content to existing file')
        ];
    }

    public $toolbarTools = [
        'text' => [
            'title' => 'Text',
            'icon' => 'fas fa-pencil-alt',
            'type' => 'input'
        ],
        'email' => [
            'title' => 'Email',
            'icon' => 'far fa-envelope',
            'type' => 'input'
        ],
        'number' => [
            'title' => 'Number',
            'icon' => 'far leform-number-icon',
            'type' => 'input'
        ],
        /*
        'numspinner' => [
            'title' => 'Numeric spinner',
            'icon' => 'fas fa-sort-numeric-down',
            'type' => 'input'
        ],
         */
        'textarea' => [
            'title' => 'Textarea',
            'icon' => 'fas fa-align-left',
            'type' => 'input'
        ],
        'select' => [
            'title' => 'Select box',
            'icon' => 'far fa-caret-square-down',
            'type' => 'input'
        ],
        'checkbox' => [
            'title' => 'Checkbox',
            'icon' => 'far fa-check-square',
            'type' => 'input'
        ],
        'radio' => [
            'title' => 'Radio Button',
            'icon' => 'far fa-dot-circle',
            'type' => 'input'
        ],
        'matrix' => [
            'title' => 'Matrix',
            'icon' => 'fas fa-th',
            'type' => 'input'
        ],
        'repeater-input' => [
            'title' => 'Repeater input',
            'icon' => 'fas fa-grip-lines',
            'type' => 'input'
        ],
        'iban-input' => [
            'title' => 'IBAN & BIC input',
            'icon' => 'fas fa-money-check-alt',
            'type' => 'input'
        ],
        'multiselect' => [
            'title' => 'Multiselect',
            'icon' => 'fas fa-list-ul',
            'type' => 'input'
        ],
        'imageselect' => [
            'title' => 'Image Select',
            'icon' => 'far fa-images',
            'type' => 'input'
        ],
        'tile' => [
            'title' => 'Tile',
            'icon' => 'far leform-tile-icon',
            'type' => 'input'
        ],
        'date' => [
            'title' => 'Date',
            'icon' => 'far fa-calendar-alt',
            'type' => 'input'
        ],
        'time' => [
            'title' => 'Time',
            'icon' => 'far fa-clock',
            'type' => 'input'
        ],
        'file' => [
            'title' => 'File upload',
            'icon' => 'fas fa-upload',
            'type' => 'input'
        ],
        'password' => [
            'title' => 'Password',
            'icon' => 'fas fa-lock',
            'type' => 'input'
        ],
        'signature' => [
            'title' => 'Signature Pad',
            'icon' => 'fas fa-signature',
            'type' => 'input'
        ],
        'rangeslider' => [
            'title' => 'Range Slider',
            'icon' => 'fas fa-sliders-h',
            'type' => 'input'
        ],
        'star-rating' => [
            'title' => 'Star rating',
            'icon' => 'far fa-star',
            'type' => 'input'
        ],
        'hidden' => [
            'title' => 'Hidden field',
            'icon' => 'far fa-eye-slash',
            'type' => 'input'
        ],
        'button' => [
            'title' => 'Button',
            'icon' => 'far fa-paper-plane',
            'type' => 'submit'
        ],
        'columns' => [
            'title' => 'Column layout',
            'icon' => 'fas fa-columns',
            'options' => [
                '1' => '1 columns',
                '2' => '2 columns',
                '3' => '3 columns',
                '4' => '4 columns',
                '6' => '6 columns'
            ],
            'type' => 'other'
        ],
        'html' => [
            'title' => 'HTML',
            'icon' => 'fas fa-code',
            'type' => 'other'
        ],
        'background-image' => [
            'title' => 'Background image',
            'icon' => 'far fa-image',
            'type' => 'other'
        ],
        'link-button' => [
            'title' => 'Link Button',
            'icon' => 'fas fa-link',
            'type' => 'other'
        ],
    ];

    public $fontAwesomeIcons = [
        'solid' => ["ad", "address-book", "address-card", "adjust", "air-freshener", "align-center", "align-justify", "align-left", "align-right", "allergies", "ambulance", "american-sign-language-interpreting", "anchor", "angle-double-down", "angle-double-left", "angle-double-right", "angle-double-up", "angle-down", "angle-left", "angle-right", "angle-up", "angry", "ankh", "apple-alt", "archive", "archway", "arrow-alt-circle-down", "arrow-alt-circle-left", "arrow-alt-circle-right", "arrow-alt-circle-up", "arrow-circle-down", "arrow-circle-left", "arrow-circle-right", "arrow-circle-up", "arrow-down", "arrow-left", "arrow-right", "arrow-up", "arrows-alt", "arrows-alt-h", "arrows-alt-v", "assistive-listening-systems", "asterisk", "at", "atlas", "atom", "audio-description", "award", "baby", "baby-carriage", "backspace", "backward", "bacon", "balance-scale", "ban", "band-aid", "barcode", "bars", "baseball-ball", "basketball-ball", "bath", "battery-empty", "battery-full", "battery-half", "battery-quarter", "battery-three-quarters", "bed", "beer", "bell", "bell-slash", "bezier-curve", "bible", "bicycle", "binoculars", "biohazard", "birthday-cake", "blender", "blender-phone", "blind", "blog", "bold", "bolt", "bomb", "bone", "bong", "book", "book-dead", "book-medical", "book-open", "book-reader", "bookmark", "bowling-ball", "box", "box-open", "boxes", "braille", "brain", "bread-slice", "briefcase", "briefcase-medical", "broadcast-tower", "broom", "brush", "bug", "building", "bullhorn", "bullseye", "burn", "bus", "bus-alt", "business-time", "calculator", "calendar", "calendar-alt", "calendar-check", "calendar-day", "calendar-minus", "calendar-plus", "calendar-times", "calendar-week", "camera", "camera-retro", "campground", "candy-cane", "cannabis", "capsules", "car", "car-alt", "car-battery", "car-crash", "car-side", "caret-down", "caret-left", "caret-right", "caret-square-down", "caret-square-left", "caret-square-right", "caret-square-up", "caret-up", "carrot", "cart-arrow-down", "cart-plus", "cash-register", "cat", "certificate", "chair", "chalkboard", "chalkboard-teacher", "charging-station", "chart-area", "chart-bar", "chart-line", "chart-pie", "check", "check-circle", "check-double", "check-square", "cheese", "chess", "chess-bishop", "chess-board", "chess-king", "chess-knight", "chess-pawn", "chess-queen", "chess-rook", "chevron-circle-down", "chevron-circle-left", "chevron-circle-right", "chevron-circle-up", "chevron-down", "chevron-left", "chevron-right", "chevron-up", "child", "church", "circle", "circle-notch", "city", "clinic-medical", "clipboard", "clipboard-check", "clipboard-list", "clock", "clone", "closed-captioning", "cloud", "cloud-download-alt", "cloud-meatball", "cloud-moon", "cloud-moon-rain", "cloud-rain", "cloud-showers-heavy", "cloud-sun", "cloud-sun-rain", "cloud-upload-alt", "cocktail", "code", "code-branch", "coffee", "cog", "cogs", "coins", "columns", "comment", "comment-alt", "comment-dollar", "comment-dots", "comment-medical", "comment-slash", "comments", "comments-dollar", "compact-disc", "compass", "compress", "compress-arrows-alt", "concierge-bell", "cookie", "cookie-bite", "copy", "copyright", "couch", "credit-card", "crop", "crop-alt", "cross", "crosshairs", "crow", "crown", "crutch", "cube", "cubes", "cut", "database", "deaf", "democrat", "desktop", "dharmachakra", "diagnoses", "dice", "dice-d20", "dice-d6", "dice-five", "dice-four", "dice-one", "dice-six", "dice-three", "dice-two", "digital-tachograph", "directions", "divide", "dizzy", "dna", "dog", "dollar-sign", "dolly", "dolly-flatbed", "donate", "door-closed", "door-open", "dot-circle", "dove", "download", "drafting-compass", "dragon", "draw-polygon", "drum", "drum-steelpan", "drumstick-bite", "dumbbell", "dumpster", "dumpster-fire", "dungeon", "edit", "egg", "eject", "ellipsis-h", "ellipsis-v", "envelope", "envelope-open", "envelope-open-text", "envelope-square", "equals", "eraser", "ethernet", "euro-sign", "exchange-alt", "exclamation", "exclamation-circle", "exclamation-triangle", "expand", "expand-arrows-alt", "external-link-alt", "external-link-square-alt", "eye", "eye-dropper", "eye-slash", "fast-backward", "fast-forward", "fax", "feather", "feather-alt", "female", "fighter-jet", "file", "file-alt", "file-archive", "file-audio", "file-code", "file-contract", "file-csv", "file-download", "file-excel", "file-export", "file-image", "file-import", "file-invoice", "file-invoice-dollar", "file-medical", "file-medical-alt", "file-pdf", "file-powerpoint", "file-prescription", "file-signature", "file-upload", "file-video", "file-word", "fill", "fill-drip", "film", "filter", "fingerprint", "fire", "fire-alt", "fire-extinguisher", "first-aid", "fish", "fist-raised", "flag", "flag-checkered", "flag-usa", "flask", "flushed", "folder", "folder-minus", "folder-open", "folder-plus", "font", "football-ball", "forward", "frog", "frown", "frown-open", "funnel-dollar", "futbol", "gamepad", "gas-pump", "gavel", "gem", "genderless", "ghost", "gift", "gifts", "glass-cheers", "glass-martini", "glass-martini-alt", "glass-whiskey", "glasses", "globe", "globe-africa", "globe-americas", "globe-asia", "globe-europe", "golf-ball", "gopuram", "graduation-cap", "greater-than", "greater-than-equal", "grimace", "grin", "grin-alt", "grin-beam", "grin-beam-sweat", "grin-hearts", "grin-squint", "grin-squint-tears", "grin-stars", "grin-tears", "grin-tongue", "grin-tongue-squint", "grin-tongue-wink", "grin-wink", "grip-horizontal", "grip-lines", "grip-lines-vertical", "grip-vertical", "guitar", "h-square", "hamburger", "hammer", "hamsa", "hand-holding", "hand-holding-heart", "hand-holding-usd", "hand-lizard", "hand-middle-finger", "hand-paper", "hand-peace", "hand-point-down", "hand-point-left", "hand-point-right", "hand-point-up", "hand-pointer", "hand-rock", "hand-scissors", "hand-spock", "hands", "hands-helping", "handshake", "hanukiah", "hard-hat", "hashtag", "hat-wizard", "haykal", "hdd", "heading", "headphones", "headphones-alt", "headset", "heart", "heart-broken", "heartbeat", "helicopter", "highlighter", "hiking", "hippo", "history", "hockey-puck", "holly-berry", "home", "horse", "horse-head", "hospital", "hospital-alt", "hospital-symbol", "hot-tub", "hotdog", "hotel", "hourglass", "hourglass-end", "hourglass-half", "hourglass-start", "house-damage", "hryvnia", "i-cursor", "ice-cream", "icicles", "id-badge", "id-card", "id-card-alt", "igloo", "image", "images", "inbox", "indent", "industry", "infinity", "info", "info-circle", "italic", "jedi", "joint", "journal-whills", "kaaba", "key", "keyboard", "khanda", "kiss", "kiss-beam", "kiss-wink-heart", "kiwi-bird", "landmark", "language", "laptop", "laptop-code", "laptop-medical", "laugh", "laugh-beam", "laugh-squint", "laugh-wink", "layer-group", "leaf", "lemon", "less-than", "less-than-equal", "level-down-alt", "level-up-alt", "life-ring", "lightbulb", "link", "lira-sign", "list", "list-alt", "list-ol", "list-ul", "location-arrow", "lock", "lock-open", "long-arrow-alt-down", "long-arrow-alt-left", "long-arrow-alt-right", "long-arrow-alt-up", "low-vision", "luggage-cart", "magic", "magnet", "mail-bulk", "male", "map", "map-marked", "map-marked-alt", "map-marker", "map-marker-alt", "map-pin", "map-signs", "marker", "mars", "mars-double", "mars-stroke", "mars-stroke-h", "mars-stroke-v", "mask", "medal", "medkit", "meh", "meh-blank", "meh-rolling-eyes", "memory", "menorah", "mercury", "meteor", "microchip", "microphone", "microphone-alt", "microphone-alt-slash", "microphone-slash", "microscope", "minus", "minus-circle", "minus-square", "mitten", "mobile", "mobile-alt", "money-bill", "money-bill-alt", "money-bill-wave", "money-bill-wave-alt", "money-check", "money-check-alt", "monument", "moon", "mortar-pestle", "mosque", "motorcycle", "mountain", "mouse-pointer", "mug-hot", "music", "network-wired", "neuter", "newspaper", "not-equal", "notes-medical", "object-group", "object-ungroup", "oil-can", "om", "otter", "outdent", "pager", "paint-brush", "paint-roller", "palette", "pallet", "paper-plane", "paperclip", "parachute-box", "paragraph", "parking", "passport", "pastafarianism", "paste", "pause", "pause-circle", "paw", "peace", "pen", "pen-alt", "pen-fancy", "pen-nib", "pen-square", "pencil-alt", "pencil-ruler", "people-carry", "pepper-hot", "percent", "percentage", "person-booth", "phone", "phone-slash", "phone-square", "phone-volume", "piggy-bank", "pills", "pizza-slice", "place-of-worship", "plane", "plane-arrival", "plane-departure", "play", "play-circle", "plug", "plus", "plus-circle", "plus-square", "podcast", "poll", "poll-h", "poo", "poo-storm", "poop", "portrait", "pound-sign", "power-off", "pray", "praying-hands", "prescription", "prescription-bottle", "prescription-bottle-alt", "print", "procedures", "project-diagram", "puzzle-piece", "qrcode", "question", "question-circle", "quidditch", "quote-left", "quote-right", "quran", "radiation", "radiation-alt", "rainbow", "random", "receipt", "recycle", "redo", "redo-alt", "registered", "reply", "reply-all", "republican", "restroom", "retweet", "ribbon", "ring", "road", "robot", "rocket", "route", "rss", "rss-square", "ruble-sign", "ruler", "ruler-combined", "ruler-horizontal", "ruler-vertical", "running", "rupee-sign", "sad-cry", "sad-tear", "satellite", "satellite-dish", "save", "school", "screwdriver", "scroll", "sd-card", "search", "search-dollar", "search-location", "search-minus", "search-plus", "seedling", "server", "shapes", "share", "share-alt", "share-alt-square", "share-square", "shekel-sign", "shield-alt", "ship", "shipping-fast", "shoe-prints", "shopping-bag", "shopping-basket", "shopping-cart", "shower", "shuttle-van", "sign", "sign-in-alt", "sign-language", "sign-out-alt", "signal", "signature", "sim-card", "sitemap", "skating", "skiing", "skiing-nordic", "skull", "skull-crossbones", "slash", "sleigh", "sliders-h", "smile", "smile-beam", "smile-wink", "smog", "smoking", "smoking-ban", "sms", "snowboarding", "snowflake", "snowman", "snowplow", "socks", "solar-panel", "sort", "sort-alpha-down", "sort-alpha-up", "sort-amount-down", "sort-amount-up", "sort-down", "sort-numeric-down", "sort-numeric-up", "sort-up", "spa", "space-shuttle", "spider", "spinner", "splotch", "spray-can", "square", "square-full", "square-root-alt", "stamp", "star", "star-and-crescent", "star-half", "star-half-alt", "star-of-david", "star-of-life", "step-backward", "step-forward", "stethoscope", "sticky-note", "stop", "stop-circle", "stopwatch", "store", "store-alt", "stream", "street-view", "strikethrough", "stroopwafel", "subscript", "subway", "suitcase", "suitcase-rolling", "sun", "superscript", "surprise", "swatchbook", "swimmer", "swimming-pool", "synagogue", "sync", "sync-alt", "syringe", "table", "table-tennis", "tablet", "tablet-alt", "tablets", "tachometer-alt", "tag", "tags", "tape", "tasks", "taxi", "teeth", "teeth-open", "temperature-high", "temperature-low", "tenge", "terminal", "text-height", "text-width", "th", "th-large", "th-list", "theater-masks", "thermometer", "thermometer-empty", "thermometer-full", "thermometer-half", "thermometer-quarter", "thermometer-three-quarters", "thumbs-down", "thumbs-up", "thumbtack", "ticket-alt", "times", "times-circle", "tint", "tint-slash", "tired", "toggle-off", "toggle-on", "toilet", "toilet-paper", "toolbox", "tools", "tooth", "torah", "torii-gate", "tractor", "trademark", "traffic-light", "train", "tram", "transgender", "transgender-alt", "trash", "trash-alt", "trash-restore", "trash-restore-alt", "tree", "trophy", "truck", "truck-loading", "truck-monster", "truck-moving", "truck-pickup", "tshirt", "tty", "tv", "umbrella", "umbrella-beach", "underline", "undo", "undo-alt", "universal-access", "university", "unlink", "unlock", "unlock-alt", "upload", "user", "user-alt", "user-alt-slash", "user-astronaut", "user-check", "user-circle", "user-clock", "user-cog", "user-edit", "user-friends", "user-graduate", "user-injured", "user-lock", "user-md", "user-minus", "user-ninja", "user-nurse", "user-plus", "user-secret", "user-shield", "user-slash", "user-tag", "user-tie", "user-times", "users", "users-cog", "utensil-spoon", "utensils", "vector-square", "venus", "venus-double", "venus-mars", "vial", "vials", "video", "video-slash", "vihara", "volleyball-ball", "volume-down", "volume-mute", "volume-off", "volume-up", "vote-yea", "vr-cardboard", "walking", "wallet", "warehouse", "water", "weight", "weight-hanging", "wheelchair", "wifi", "wind", "window-close", "window-maximize", "window-minimize", "window-restore", "wine-bottle", "wine-glass", "wine-glass-alt", "won-sign", "wrench", "x-ray", "yen-sign", "yin-yang"],
        'regular' => ["address-book", "address-card", "angry", "arrow-alt-circle-down", "arrow-alt-circle-left", "arrow-alt-circle-right", "arrow-alt-circle-up", "bell", "bell-slash", "bookmark", "building", "calendar", "calendar-alt", "calendar-check", "calendar-minus", "calendar-plus", "calendar-times", "caret-square-down", "caret-square-left", "caret-square-right", "caret-square-up", "chart-bar", "check-circle", "check-square", "circle", "clipboard", "clock", "clone", "closed-captioning", "comment", "comment-alt", "comment-dots", "comments", "compass", "copy", "copyright", "credit-card", "dizzy", "dot-circle", "edit", "envelope", "envelope-open", "eye", "eye-slash", "file", "file-alt", "file-archive", "file-audio", "file-code", "file-excel", "file-image", "file-pdf", "file-powerpoint", "file-video", "file-word", "flag", "flushed", "folder", "folder-open", "frown", "frown-open", "futbol", "gem", "grimace", "grin", "grin-alt", "grin-beam", "grin-beam-sweat", "grin-hearts", "grin-squint", "grin-squint-tears", "grin-stars", "grin-tears", "grin-tongue", "grin-tongue-squint", "grin-tongue-wink", "grin-wink", "hand-lizard", "hand-paper", "hand-peace", "hand-point-down", "hand-point-left", "hand-point-right", "hand-point-up", "hand-pointer", "hand-rock", "hand-scissors", "hand-spock", "handshake", "hdd", "heart", "hospital", "hourglass", "id-badge", "id-card", "image", "images", "keyboard", "kiss", "kiss-beam", "kiss-wink-heart", "laugh", "laugh-beam", "laugh-squint", "laugh-wink", "lemon", "life-ring", "lightbulb", "list-alt", "map", "meh", "meh-blank", "meh-rolling-eyes", "minus-square", "money-bill-alt", "moon", "newspaper", "object-group", "object-ungroup", "paper-plane", "pause-circle", "play-circle", "plus-square", "question-circle", "registered", "sad-cry", "sad-tear", "save", "share-square", "smile", "smile-beam", "smile-wink", "snowflake", "square", "star", "star-half", "sticky-note", "stop-circle", "sun", "surprise", "thumbs-down", "thumbs-up", "times-circle", "tired", "trash-alt", "user", "user-circle", "window-close", "window-maximize", "window-minimize", "window-restore"],
        'brands' => ["500px", "accessible-icon", "accusoft", "acquisitions-incorporated", "adn", "adobe", "adversal", "affiliatetheme", "algolia", "alipay", "amazon", "amazon-pay", "amilia", "android", "angellist", "angrycreative", "angular", "app-store", "app-store-ios", "apper", "apple", "apple-pay", "artstation", "asymmetrik", "atlassian", "audible", "autoprefixer", "avianex", "aviato", "aws", "bandcamp", "behance", "behance-square", "bimobject", "bitbucket", "bitcoin", "bity", "black-tie", "blackberry", "blogger", "blogger-b", "bluetooth", "bluetooth-b", "btc", "buromobelexperte", "canadian-maple-leaf", "cc-amazon-pay", "cc-amex", "cc-apple-pay", "cc-diners-club", "cc-discover", "cc-jcb", "cc-mastercard", "cc-paypal", "cc-stripe", "cc-visa", "centercode", "centos", "chrome", "cloudscale", "cloudsmith", "cloudversify", "codepen", "codiepie", "confluence", "connectdevelop", "contao", "cpanel", "creative-commons", "creative-commons-by", "creative-commons-nc", "creative-commons-nc-eu", "creative-commons-nc-jp", "creative-commons-nd", "creative-commons-pd", "creative-commons-pd-alt", "creative-commons-remix", "creative-commons-sa", "creative-commons-sampling", "creative-commons-sampling-plus", "creative-commons-share", "creative-commons-zero", "critical-role", "css3", "css3-alt", "cuttlefish", "d-and-d", "d-and-d-beyond", "dashcube", "delicious", "deploydog", "deskpro", "dev", "deviantart", "dhl", "diaspora", "digg", "digital-ocean", "discord", "discourse", "dochub", "docker", "draft2digital", "dribbble", "dribbble-square", "dropbox", "drupal", "dyalog", "earlybirds", "ebay", "edge", "elementor", "ello", "ember", "empire", "envira", "erlang", "ethereum", "etsy", "expeditedssl", "facebook", "facebook-f", "facebook-messenger", "facebook-square", "fantasy-flight-games", "fedex", "fedora", "figma", "firefox", "first-order", "first-order-alt", "firstdraft", "flickr", "flipboard", "fly", "font-awesome", "font-awesome-alt", "font-awesome-flag", "fonticons", "fonticons-fi", "fort-awesome", "fort-awesome-alt", "forumbee", "foursquare", "free-code-camp", "freebsd", "fulcrum", "galactic-republic", "galactic-senate", "get-pocket", "gg", "gg-circle", "git", "git-square", "github", "github-alt", "github-square", "gitkraken", "gitlab", "gitter", "glide", "glide-g", "gofore", "goodreads", "goodreads-g", "google", "google-drive", "google-play", "google-plus", "google-plus-g", "google-plus-square", "google-wallet", "gratipay", "grav", "gripfire", "grunt", "gulp", "hacker-news", "hacker-news-square", "hackerrank", "hips", "hire-a-helper", "hooli", "hornbill", "hotjar", "houzz", "html5", "hubspot", "imdb", "instagram", "intercom", "internet-explorer", "invision", "ioxhost", "itunes", "itunes-note", "java", "jedi-order", "jenkins", "jira", "joget", "joomla", "js", "js-square", "jsfiddle", "kaggle", "keybase", "keycdn", "kickstarter", "kickstarter-k", "korvue", "laravel", "lastfm", "lastfm-square", "leanpub", "less", "line", "linkedin", "linkedin-in", "linode", "linux", "lyft", "magento", "mailchimp", "mandalorian", "markdown", "mastodon", "maxcdn", "medapps", "medium", "medium-m", "medrt", "meetup", "megaport", "mendeley", "microsoft", "mix", "mixcloud", "mizuni", "modx", "monero", "napster", "neos", "nimblr", "nintendo-switch", "node", "node-js", "npm", "ns8", "nutritionix", "odnoklassniki", "odnoklassniki-square", "old-republic", "opencart", "openid", "opera", "optin-monster", "osi", "page4", "pagelines", "palfed", "patreon", "paypal", "penny-arcade", "periscope", "phabricator", "phoenix-framework", "phoenix-squadron", "php", "pied-piper", "pied-piper-alt", "pied-piper-hat", "pied-piper-pp", "pinterest", "pinterest-p", "pinterest-square", "playstation", "product-hunt", "pushed", "python", "qq", "quinscape", "quora", "r-project", "raspberry-pi", "ravelry", "react", "reacteurope", "readme", "rebel", "red-river", "reddit", "reddit-alien", "reddit-square", "redhat", "renren", "replyd", "researchgate", "resolving", "rev", "rocketchat", "rockrms", "safari", "sass", "schlix", "scribd", "searchengin", "sellcast", "sellsy", "servicestack", "shirtsinbulk", "shopware", "simplybuilt", "sistrix", "sith", "sketch", "skyatlas", "skype", "slack", "slack-hash", "slideshare", "snapchat", "snapchat-ghost", "snapchat-square", "soundcloud", "sourcetree", "speakap", "spotify", "squarespace", "stack-exchange", "stack-overflow", "staylinked", "steam", "steam-square", "steam-symbol", "sticker-mule", "strava", "stripe", "stripe-s", "studiovinari", "stumbleupon", "stumbleupon-circle", "superpowers", "supple", "suse", "teamspeak", "telegram", "telegram-plane", "tencent-weibo", "the-red-yeti", "themeco", "themeisle", "think-peaks", "trade-federation", "trello", "tripadvisor", "tumblr", "tumblr-square", "twitch", "twitter", "twitter-square", "typo3", "uber", "ubuntu", "uikit", "uniregistry", "untappd", "ups", "usb", "usps", "ussunnah", "vaadin", "viacoin", "viadeo", "viadeo-square", "viber", "vimeo", "vimeo-square", "vimeo-v", "vine", "vk", "vnv", "vuejs", "weebly", "weibo", "weixin", "whatsapp", "whatsapp-square", "whmcs", "wikipedia-w", "windows", "wix", "wizards-of-the-coast", "wolf-pack-battalion", "wordpress", "wordpress-simple", "wpbeginner", "wpexplorer", "wpforms", "wpressr", "xbox", "xing", "xing-square", "y-combinator", "yahoo", "yandex", "yandex-international", "yarn", "yelp", "yoast", "youtube", "youtube-square", "zhihu"],
        'basic' => ["star", "star-o", "check", "close", "lock", "picture-o", "upload", "download", "calendar", "clock-o", "chevron-left", "chevron-right", "phone", "envelope", "envelope-o", "pencil", "angle-double-left", "angle-double-right", "spinner", "smile-o", "frown-o", "meh-o", "send", "send-o", "user", "user-o", "building-o"],
    ];

    public $options = [
        #"from-name" => get_bloginfo('name'),
        #"from-email" => "noreply@".str_replace("www.", "", $domain),
        "from-name" => '',
        "from-email" => '',
        "fa-enable" => "on",
        "fa-solid-enable" => "on",
        "fa-regular-enable" => "off",
        "fa-brands-enable" => "off",
        "fa-css-disable" => "off",
        "ga-tracking" => "off",
        "mask-enable" => "on",
        "mask-js-disable" => "off",
        "airdatepicker-enable" => "on",
        "airdatepicker-js-disable" => "off",
        "jsep-enable" => "on",
        "jsep-js-disable" => "off",
        "signature-enable" => "off",
        "signature-js-disable" => "off",
        "range-slider-enable" => "on",
        "range-slider-js-disable" => "off",
        "tooltipster-enable" => "off",
        "tooltipster-js-disable" => "off",
        "purchase-code" => "",
        "csv-separator" => ";",
        "email-validator" => "basic",
        "file-autodelete" => "none",
        "sort-forms" => 'date-za',
        "sort-log" => 'date-za',
        "gettingstarted-enable" => 'on'
    ];

    public $predefinedOptions = [
        'countries' => [
            'label' => 'Countries',
            'options' => ["Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua And Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia And Herzegovina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling] Islands", "Colombia", "Comoros", "Congo", "Congo, The Democratic Republic Of The", "Cook Islands", "Costa Rica", "Cote D'Ivoire", "Croatia (Local Name: Hrvatska]", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas]", "Faroe Islands", "Fiji", "Finland", "France", "France, Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard And Mc Donald Islands", "Holy See (Vatican City State]", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic Of]", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic Of", "Korea, Republic Of", "Kuwait", "Kyrgyzstan", "Lao People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, Former Yugoslav Republic Of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States Of", "Moldova, Republic Of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts And Nevis", "Saint Lucia", "Saint Vincent And The Grenadines", "Samoa", "San Marino", "Sao Tome And Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic]", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia, South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre And Miquelon", "Sudan", "Suriname", "Svalbard And Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan", "Tajikistan", "Tanzania, United Republic Of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad And Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks And Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British]", "Virgin Islands (U.S.]", "Wallis And Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe"]
        ],
        'us-states' => [
            'label' => 'U.S. States',
            'options' => ["Alabama", "Alaska", "Arizona", "Arkansas", "California", "Colorado", "Connecticut", "Delaware", "District Of Columbia", "Florida", "Georgia", "Hawaii", "Idaho", "Illinois", "Indiana", "Iowa", "Kansas", "Kentucky", "Louisiana", "Maine", "Maryland", "Massachusetts", "Michigan", "Minnesota", "Mississippi", "Missouri", "Montana", "Nebraska", "Nevada", "New Hampshire", "New Jersey", "New Mexico", "New York", "North Carolina", "North Dakota", "Ohio", "Oklahoma", "Oregon", "Pennsylvania", "Rhode Island", "South Carolina", "South Dakota", "Tennessee", "Texas", "Utah", "Vermont", "Virginia", "Washington", "West Virginia", "Wisconsin", "Wyoming"]
        ],
        'canadian-provinces' => [
            'label' => 'Canadian Provinces',
            'options' => ["Alberta", "British Columbia", "Manitoba", "New Brunswick", "Newfoundland & Labrador", "Northwest Territories", "Nova Scotia", "Nunavut", "Ontario", "Prince Edward Island", "Quebec", "Saskatchewan", "Yukon"]
        ],
        'uk-counties' => [
            'label' => 'UK Counties',
            'options' => ["Aberdeen City", "Aberdeenshire", "Angus", "Antrim", "Argyll and Bute", "Armagh", "Avon", "Banffshire", "Bedfordshire", "Berkshire", "Blaenau Gwent", "Borders", "Bridgend", "Bristol", "Buckinghamshire", "Caerphilly", "Cambridgeshire", "Cardiff", "Carmarthenshire", "Ceredigion", "Channel Islands", "Cheshire", "Clackmannan", "Cleveland", "Conwy", "Cornwall", "Cumbria", "Denbighshire", "Derbyshire", "Devon", "Dorset", "Down", "Dumfries and Galloway", "Durham", "East Ayrshire", "East Dunbartonshire", "East Lothian", "East Renfrewshire", "East Riding of Yorkshire", "East Sussex", "Edinburgh City", "Essex", "Falkirk", "Fermanagh", "Fife", "Flintshire", "Glasgow (City of]", "Gloucestershire", "Greater Manchester", "Gwynedd", "Hampshire", "Herefordshire", "Hertfordshire", "Highland", "Humberside", "Inverclyde", "Isle of Anglesey", "Isle of Man", "Isle of Wight", "Isles of Scilly", "Kent", "Lancashire", "Leicestershire", "Lincolnshire", "London", "Londonderry", "Merseyside", "Merthyr Tydfil", "Middlesex", "Midlothian", "Monmouthshire", "Moray", "Neath Port Talbot", "Newport", "Norfolk", "North Ayrshire", "North East Lincolnshire", "North Lanarkshire", "North Yorkshire", "Northamptonshire", "Northumberland", "Nottinghamshire", "Orkney", "Oxfordshire", "Pembrokeshire", "Perthshire and Kinross", "Powys", "Renfrewshire", "Rhondda Cynon Taff", "Roxburghshire", "Rutland", "Shetland", "Shropshire", "Somerset", "South Ayrshire", "South Lanarkshire", "South Yorkshire", "Staffordshire", "Stirling", "Suffolk", "Surrey", "Swansea", "The Vale of Glamorgan", "Torfaen", "Tyne and Wear", "Tyrone", "Warwickshire", "West Dunbartonshire", "West Lothian", "West Midlands", "West Sussex", "West Yorkshire", "Western Isles", "Wiltshire", "Worcestershire", "Wrexham"]
        ],
        'german-states' => [
            'label' => 'German States',
            'options' => ["Baden-Wurttemberg", "Bavaria", "Berlin", "Brandenburg", "Bremen", "Hamburg", "Hesse", "Lower Saxony", "Mecklenburg-West Pomerania", "North Rhine-Westphalia", "Rhineland-Palatinate", "Saarland", "Saxony", "Saxony-Anhalt", "Schleswig-Holstein", "Thuringia"]
        ],
        'dutch-provinces' => [
            'label' => 'Dutch Provinces',
            'options' => ["Drente", "Flevoland", "Friesland", "Gelderland", "Groningen", "Limburg", "Noord-Brabant", "Noord-Holland", "Overijssel", "Utrecht", "Zeeland", "Zuid-Holland"]
        ],
        'australian-states' => [
            'label' => 'Australian States',
            'options' => ["Australian Capital Territory", "New South Wales", "Northern Territory", "Queensland", "South Australia", "Tasmania", "Victoria", "Western Australia"]
        ],
        'continents' => [
            'label' => 'Continents',
            'options' => ["Africa", "Antarctica", "Asia", "Australia", "Europe", "North America", "South America"]
        ],
        'days' => [
            'label' => 'Days',
            'options' => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]
        ],
        'months' => [
            'label' => 'Months',
            'options' => ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"]
        ]
    ];

    public function getElementPropertiesMeta()
    {
        return [
            'settings' => [
                'general-tab' => ['type' => 'tab', 'value' => 'general', 'label' => 'General'],
                'name' => ['value' => 'Untitled', 'label' => 'Name', 'tooltip' => 'The name helps to identify the form.', 'type' => 'text'],
                'show-on-api' => [
                    'value' => 'off',
                    'label' => 'Synch form via API',
                    'type' => 'checkbox'
                ],
                'track-count-anonymously' => [
                    'value' => 'on',
                    'label' => 'Track count of filled forms in Moodle anonymously',
                    'type' => 'checkbox'
                ],
                'required-token' => [
                    'value' => 'off',
                    'label' => 'Force token parameters for calling the form',
                    'tooltip' => 'If the option is active, the form can be called only if the URL contains a "token" parameter. The form is not displayed and replaced by a message (see below)',
                    'type' => 'checkbox'
                ],
                'required-token-description' => [
                    'value' => 'Dieses Formular kann nur mit einem gültigen Token aufgerufen werden. Bitte öffnen Sie das Formular erneut aus der ursprünglichen Anwendung heraus.',
                    'label' => 'Token parameters Note',
                    'tooltip' => 'Displayed instead of the form content if the token parameter is missing',
                    'type' => 'textarea',
                    'visible' => ['required-token' => ['on']],
                ],
                'has-dynamic-name-values' => [
                    'value' => 'off',
                    'label' => 'Has dynamic name values',
                    'tooltip' => 'Has dynamic name values tooltip',
                    'type' => 'checkbox'
                ],
                'dynamic-name-values' => [
                    'value' => '',
                    'label' => 'Dynamic name values',
                    'tooltip' => 'Dynamic name values tooltip',
                    'type' => 'text',
                    'visible' => ['has-dynamic-name-values' => ['on']],
                ],

                'active' => [
                    'value' => 'on',
                    'label' => 'Active',
                    'tooltip' => 'Inactive forms will not appear on the site.',
                    'type' => 'checkbox'
                ],
                'key-fields' => [
                    'value' => ['primary' => '', 'secondary' => ''],
                    'caption' => ['primary' => 'Primary field', 'secondary' => 'Secondary field'],
                    'placeholder' => [
                        'primary' => 'Select primary field',
                        'secondary' => 'Select secondary field'
                    ],
                    'label' => 'Key fields',
                    'tooltip' => 'The values of these fields are displayed on Log page in relevant columns.',
                    'type' => 'key-fields',
                ],
                'datetime-args' => [
                    'value' => ['date-format' => 'dd.mm.yyyy', 'time-format' => 'hh:ii', 'locale' => 'de'],
                    'label' => 'Date and time parameters',
                    'tooltip' => 'Choose the date and time formats and language for datetimepicker. It is used for "date" and "time" fields.',
                    'type' => 'datetime-args',
                    'date-format-options' => ['dd.mm.yyyy' => 'DD.MM.YYYY', 'yyyy-mm-dd' => 'YYYY-MM-DD', 'mm/dd/yyyy' => 'MM/DD/YYYY', 'dd/mm/yyyy' => 'DD/MM/YYYY'],
                    'date-format-label' => 'Date format',
                    'time-format-options' => ['hh:ii aa' => '12 hours', 'hh:ii' => '24 hours'],
                    'time-format-label' => 'Time format',
                    'locale-options' => ['en', 'cs', 'da', 'de', 'es', 'fi', 'fr', 'hu', 'nl', 'pl', 'pt', 'ro', 'ru', 'sk', 'tr', 'zh'],
                    'locale-label' => 'Language',
                ],
                'cross-domain' => [
                    'value' => 'off',
                    'label' => 'Cross-domain calls',
                    'tooltip' => 'Enable this option if you want to use cross-domain embedding, i.e. plugin installed on domain1, and form is used on domain2. Due to security reasons this feature is automatically disabled if the form has Signature field.',
                    'type' => 'checkbox',
                ],
                'session-enable' => [
                    'value' => 'off',
                    'label' => 'Enable sessions',
                    'tooltip' => 'Activate this option if you want to enable sessions for the form. Session allows to keep non-completed form data, so user can continue form filling when come back.',
                    'type' => 'checkbox',
                ],
                'session-length' => [
                    'value' => '48',
                    'label' => 'Session length',
                    'tooltip' => 'Specify how many hours non-completed data are kept.',
                    'unit' => 'hrs',
                    'type' => 'units',
                    'visible' => ['session-enable' => ['on']],
                ],



                'redirect-enable' => [
                    'value' => 'off',
                    'label' => 'Enable redirect',
                    'tooltip' => 'Activate this option if you want to enable sessions for the form. Session allows to keep non-completed form data, so user can continue form filling when come back.',
                    'type' => 'checkbox',
                ],
                'redirect-url' => [
                    'value' => '#',
                    'label' => 'Redirect Url',
                    'tooltip' => 'Specify how many hours non-completed data are kept.',
                    'type' => 'text',
                    'visible' => ['redirect-enable' => ['on']],
                ],
                'form-background-first-page' => [
                    'value' => [
                        'top' => '0',
                        'bottom' => '0',
                        'left' => '0',
                        'right' => '0',
                        'file' => '',
                    ],
                    'label' => 'Specify the first page pdf background template',
                    'tooltip' => 'Specify the first pdf background template',
                    'type' => 'form-background',
                ],
                'form-background-other-page' => [
                    'value' => [
                        'top' => '0',
                        'bottom' => '0',
                        'left' => '0',
                        'right' => '0',
                        'file' => '',
                    ],
                    'label' => 'Specify other page pdf background template',
                    'tooltip' => 'Specify the next pdf background template',
                    'type' => 'form-background',
                ],





                'style-tab' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'style' => [
                    'caption' => ['style' => 'Load theme.'],
                    'label' => 'Theme',
                    'tooltip' => 'Load existing theme or save current one. All parameters on "Style" tab will be overwritten once you load a theme.',
                    'type' => 'style',
                ],
                'style-sections' => [
                    'type' => 'sections',
                    'sections' => [
                        'global' => ['label' => 'Global', 'icon' => 'fas fa-globe'],
                        'labels' => ['label' => 'Labels', 'icon' => 'fas fa-font'],
                        'inputs' => ['label' => 'Inputs', 'icon' => 'fas fa-pencil-alt'],
                        'buttons' => ['label' => 'Buttons', 'icon' => 'far fa-paper-plane'],
                        'errors' => ['label' => 'Errors', 'icon' => 'far fa-hand-paper'],
                        'progress' => ['label' => 'Progress Bar', 'icon' => 'fas fa-sliders-h'],
                    ],
                ],
                'start-global' => ['type' => 'section-start', 'section' => 'global'],
                'text-style' => [
                    'value' => ['family' => 'arial', 'size' => '15', 'color' => '#444', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'left'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Text style',
                    'tooltip' => 'Adjust the text style.',
                    'type' => 'text-style',
                    'group' => 'style',
                ],
                'hr-1' => ['type' => 'hr'],
                'wrapper-style-sections' => [
                    'type' => 'sections',
                    'sections' => [
                        'wrapper-inline' => ['label' => 'Inline Mode', 'icon' => 'fab fa-wpforms'],
                        'wrapper-popup' => ['label' => 'Popup Mode', 'icon' => 'far fa-window-maximize'],
                    ],
                ],
                'start-wrapper-inline' => ['type' => 'section-start', 'section' => 'wrapper-inline'],
                'inline-background-style' => [
                    'value' => ['image' => '', 'size' => 'auto', 'horizontal-position' => 'left', 'vertical-position' => 'top', 'repeat' => 'repeat', 'color' => '', 'color2' => '', 'gradient' => 'no'],
                    'caption' => [
                        'image' => 'Image URL',
                        'size' => 'Size',
                        'horizontal-position' => 'Horizontal position',
                        'vertical-position' => 'Verical position',
                        'repeat' => 'Repeat',
                        'color' => 'Color',
                        'color2' => 'Second color',
                        'gradient' => 'Gradient',
                    ],
                    'label' => 'Wrapper background',
                    'tooltip' => 'Adjust the background style for inline view of the form.',
                    'type' => 'background-style',
                    'group' => 'style',
                ],
                'inline-border-style' => [
                    'value' => ['width' => '0', 'style' => 'solid', 'radius' => '0', 'color' => '', 'top' => 'off', 'right' => 'off', 'bottom' => 'off', 'left' => 'off'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Wrapper border',
                    'tooltip' => 'Adjust the border style for inline view of the form.',
                    'type' => 'border-style',
                    'group' => 'style',
                ],
                'inline-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Wrapper shadow',
                    'tooltip' => 'Adjust the shadow for inline view of the form.',
                    'type' => 'shadow',
                    'group' => 'style',
                ],
                'inline-padding' => [
                    'value' => ['top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20'],
                    'caption' => ['top' => 'Top', 'right' => 'Right', 'bottom' => 'Bottom', 'left' => 'Left'],
                    'label' => 'Padding',
                    'tooltip' => 'Adjust the padding for inline view of the form.',
                    'type' => 'padding',
                    'group' => 'style',
                ],
                'end-wrapper-inline' => ['type' => 'section-end'],
                'start-wrapper-popup' => ['type' => 'section-start', 'section' => 'wrapper-popup'],
                'popup-background-style' => [
                    'value' => ['image' => '', 'size' => 'auto', 'horizontal-position' => 'left', 'vertical-position' => 'top', 'repeat' => 'repeat', 'color' => '#ffffff', 'color2' => '', 'gradient' => 'no'],
                    'caption' => [
                        'image' => 'Image URL',
                        'size' => 'Size',
                        'horizontal-position' => 'Horizontal position',
                        'vertical-position' => 'Verical position',
                        'repeat' => 'Repeat',
                        'color' => 'Color',
                        'color2' => 'Second color',
                        'gradient' => 'Gradient',
                    ],
                    'label' => 'Popup background',
                    'tooltip' => 'Adjust the background style for popup view of the form.',
                    'type' => 'background-style',
                    'group' => 'style',
                ],
                'popup-border-style' => [
                    'value' => ['width' => '0', 'style' => 'solid', 'radius' => '5', 'color' => '', 'top' => 'off', 'right' => 'off', 'bottom' => 'off', 'left' => 'off'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Popup border',
                    'tooltip' => 'Adjust the border style for popup view of the form.',
                    'type' => 'border-style',
                    'group' => 'style',
                ],
                'popup-shadow' => [
                    'value' => ['style' => 'regular', 'size' => 'huge', 'color' => '#000'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Popup shadow',
                    'tooltip' => 'Adjust the shadow for popup view of the form.',
                    'type' => 'shadow',
                    'group' => 'style',
                ],
                'popup-padding' => [
                    'value' => ['top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20'],
                    'caption' => ['top' => 'Top', 'right' => 'Right', 'bottom' => 'Bottom', 'left' => 'Left'],
                    'label' => 'Padding',
                    'tooltip' => 'Adjust the padding for popup view of the form.',
                    'type' => 'padding',
                ],
                'popup-overlay-color' => ['value' => 'rgba(255,255,255,0.7)', 'label' => 'Overlay color', 'tooltip' => 'Adjust the overlay color.', 'type' => 'color', 'group' => 'style'],
                'popup-overlay-click' => ['value' => 'on', 'label' => 'Active overlay', 'tooltip' => 'If enabled, the popup will be closed when user click overlay.', 'type' => 'checkbox'],
                'popup-close-color' => [
                    'value' => ['color1' => '#FF9800', 'color2' => '#FFC107'],
                    'label' => 'Close icon colors',
                    'tooltip' => 'Adjust the color of the close icon.',
                    'caption' => ['color1' => 'Color', 'color2' => 'Hover color'],
                    'type' => 'two-colors',
                    'group' => 'style',
                ],
                'popup-spinner-color' => [
                    'value' => ['color1' => '#FF5722', 'color2' => '#FF9800', 'color3' => '#FFC107'],
                    'label' => 'Spinner colors',
                    'tooltip' => 'Adjust the color of the spinner.',
                    'caption' => ['color1' => 'Small circle', 'color2' => 'Middle circle', 'color3' => 'Large circle'],
                    'type' => 'three-colors',
                    'group' => 'style',
                ],
                'end-wrapper-popup' => ['type' => 'section-end'],
                'hr-9' => ['type' => 'hr'],
                'tooltip-anchor' => [
                    'value' => 'none',
                    'label' => 'Tooltip anchor',
                    'tooltip' => 'Select the anchor for tooltips.',
                    'type' => 'select',
                    'options' => ['none' => 'Disable tooltips', 'label' => 'Label', 'description' => 'Description', 'input' => 'Input field'],
                    'group' => 'style',
                ],
                'tooltip-theme' => [
                    'value' => 'dark',
                    'label' => 'Tooltip theme',
                    'tooltip' => 'Select the theme of tooltips.',
                    'type' => 'select',
                    'options' => ['dark' => 'Dark', 'light' => 'Light'],
                    'group' => 'style',
                ],
                'hr-2' => ['type' => 'hr'],
                'max-width' => [
                    'value' => ['value' => '720', 'unit' => 'px', 'position' => 'center'],
                    'label' => 'Form width',
                    'tooltip' => 'Specify the maximum form width and its alignment. Leave this field empty to set maximum form width as 100%.',
                    'caption' => ['value' => 'Width', 'unit' => 'Units', 'position' => 'Position'],
                    'type' => 'block-width',
                    'group' => 'style',
                ],
                'element-spacing' => [
                    'value' => '20',
                    'label' => 'Element spacing',
                    'tooltip' => 'Specify the spacing between form elements.',
                    'unit' => 'px',
                    'type' => 'units',
                    'group' => 'style',
                ],
                'responsiveness' => [
                    'value' => ['size' => '480', 'custom' => '480'],
                    'caption' => ['size' => 'Width', 'custom' => 'Custom'],
                    'label' => 'Responsiveness',
                    'tooltip' => 'At what form width should column layouts be stacked.',
                    'type' => 'select-size',
                    'options' => ['480' => 'Phone portrait (480px)', '768' => 'Phone landscape (768px)', '1024' => 'Tablet (1024px)', 'custom' => 'Custom'],
                ],


                'custom-css' => [
                    'value' => '',
                    'label' => 'Custom css',
                    'tooltip' => 'Write custom css for special needs.',
                    'type' => 'textarea',
                    'group' => 'style'
                ],


                'end-global' => ['type' => 'section-end'],
                'start-labels' => ['type' => 'section-start', 'section' => 'labels'],
                'label-text-style' => [
                    'value' => ['family' => '', 'size' => '16', 'color' => '#444', 'bold' => 'on', 'italic' => 'off', 'underline' => 'off', 'align' => 'left'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Label text style',
                    'tooltip' => 'Adjust the text style of labels.',
                    'type' => 'text-style',
                    'group' => 'style',
                ],
                'label-style' => [
                    'value' => ['position' => 'top', 'width' => '3'],
                    'caption' => ['position' => 'Position', 'width' => 'Width'],
                    'label' => 'Label position',
                    'tooltip' => 'Choose where to display the label relative to the field.',
                    'type' => 'label-position',
                ],
                'description-text-style' => [
                    'value' => ['family' => '', 'size' => '14', 'color' => '#888', 'bold' => 'off', 'italic' => 'on', 'underline' => 'off', 'align' => 'left'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Description text style',
                    'tooltip' => 'Adjust the text style of descriptions.',
                    'type' => 'text-style',
                    'group' => 'style',
                ],
                'description-style' => [
                    'value' => ['position' => 'bottom'],
                    'caption' => ['position' => 'Position'],
                    'label' => 'Description position',
                    'tooltip' => 'Choose where to display the description relative to the field.',
                    'type' => 'description-position',
                ],
                'required-position' => [
                    'value' => 'none',
                    'label' => '"Required" symbol position',
                    'tooltip' => 'Select the position of "required" symbol/text. The symbol/text is displayed for fields that are configured as "Required".',
                    'type' => 'select',
                    'options' => [
                        'none' => 'Do not display',
                        'label-left' => 'To the left of the label',
                        'label-right' => 'To the right of the label',
                        'description-left' => 'To the left of the description',
                        'description-right' => 'To the right of the description',
                    ],
                    'group' => 'style',
                ],
                'required-text' => [
                    'value' => '*',
                    'label' => '"Required" symbol/text',
                    'tooltip' => 'The symbol/text is displayed for fields that are configured as "Required".',
                    'type' => 'text',
                    'visible' => ['required-position' => ['label-left', 'label-right', 'description-left', 'description-right']],
                    'group' => 'style',
                ],
                'required-text-style' => [
                    'value' => ['family' => '', 'size' => '', 'color' => '#d9534f', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'left'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => '"Required" symbol/text style',
                    'tooltip' => 'Adjust the text style of "required" symbol/text.',
                    'type' => 'text-style',
                    'visible' => ['required-position' => ['label-left', 'label-right', 'description-left', 'description-right']],
                    'group' => 'style',
                ],
                'end-labels' => ['type' => 'section-end'],
                'start-inputs' => ['type' => 'section-start', 'section' => 'inputs'],
                'input-size' => [
                    'value' => 'medium',
                    'label' => 'Input size',
                    'tooltip' => 'Choose the size of input fields.',
                    'type' => 'select',
                    'options' => ['tiny' => 'Tiny', 'small' => 'Small', 'medium' => 'Medium', 'large' => 'Large', 'huge' => 'Huge'],
                    'group' => 'style',
                ],
                'input-icon' => [
                    'value' => [
                        'display' => 'show',
                        'position' => 'inside',
                        'size' => '20',
                        'color' => '#444',
                        'background' => '',
                        'border' => ''
                    ],
                    'caption' => [
                        'display' => 'Display',
                        'position' => 'Position',
                        'size' => 'Size',
                        'color' => 'Color',
                        'background' => 'Background',
                        'border' => 'Border',
                    ],
                    'label' => 'Icon style',
                    'tooltip' => 'Adjust the style of input field icons.',
                    'type' => 'icon-style',
                    'group' => 'style',
                ],
                'textarea-height' => ['value' => '160', 'label' => 'Textarea height', 'tooltip' => 'Set the height of textarea fields.', 'unit' => 'px', 'type' => 'units'],
                'select-arrow-color' => [
                    'value' => '#000000',
                    'label' => 'Select arrow color',
                    'tooltip' => 'Set the color of select arrow.',
                    'type' => 'color',
                    'group' => 'style',
                ],

                'input-placeholder-color' => [
                    'value' => '',
                    'label' => 'Input placeholder color',
                    'tooltip' => 'Set the placeholder color of the input',
                    'type' => 'color',
                    'group' => 'style',
                ],
                'filled-star-rating-mode' => [
                    'value' => 'on',
                    'label' => 'Filled star rating method',
                    'tooltip' => 'Set the star rating style',
                    'type' => 'checkbox',
                    'group' => 'style',
                ],
                'star-rating-color' => [
                    'value' => '',
                    'label' => 'Star rating color',
                    'tooltip' => 'Set star rating color',
                    'type' => 'color',
                    'group' => 'style',
                ],

                'html-headings-color' => [
                    'value' => '',
                    'label' => 'Html headings color',
                    'tooltip' => 'Set headings paragraph color',
                    'type' => 'color',
                    'group' => 'style',
                ],
                'html-paragraph-color' => [
                    'value' => '',
                    'label' => 'Html paragraph color',
                    'tooltip' => 'Set html paragraph color',
                    'type' => 'color',
                    'group' => 'style',
                ],
                'html-hr-color' => [
                    'value' => '',
                    'label' => 'Html hr color',
                    'tooltip' => 'Set headings hr color',
                    'type' => 'color',
                    'group' => 'style',
                ],
                "html-hr-height" => [
                    'value' => '1',
                    'label' => 'Html hr height',
                    'tooltip' => 'Set the html hr height.',
                    'unit' => 'px',
                    'type' => 'units',
                    'group' => 'style',
                ],

                'input-style-sections' => [
                    'type' => 'sections',
                    'sections' => [
                        'inputs-default' => ['label' => 'Default', 'icon' => 'fas fa-globe', 'group' => 'style'],
                        'inputs-hover' => ['label' => 'Hover', 'icon' => 'far fa-hand-pointer', 'group' => 'style'],
                        'inputs-focus' => ['label' => 'Focus', 'icon' => 'fas fa-i-cursor', 'group' => 'style'],
                    ],
                ],
                'start-inputs-default' => ['type' => 'section-start', 'section' => 'inputs-default'],
                'input-text-style' => [
                    'value' => ['family' => '', 'size' => '15', 'color' => '#444', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'left'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Input text',
                    'tooltip' => 'Adjust the text style of input fields.',
                    'type' => 'text-style',
                    'group' => 'style',
                ],
                'input-background-style' => [
                    'value' => ['image' => '', 'size' => 'auto', 'horizontal-position' => 'left', 'vertical-position' => 'top', 'repeat' => 'repeat', 'color' => '#fff', 'color2' => '', 'gradient' => 'no'],
                    'caption' => [
                        'image' => 'Image URL',
                        'size' => 'Size',
                        'horizontal-position' => 'Horizontal position',
                        'vertical-position' => 'Verical position',
                        'repeat' => 'Repeat',
                        'color' => 'Color',
                        'color2' => 'Second color',
                        'gradient' => 'Gradient',
                    ],
                    'label' => 'Input background',
                    'tooltip' => 'Adjust the background of input fields.',
                    'type' => 'background-style',
                    'group' => 'style',
                ],
                'input-border-style' => [
                    'value' => ['width' => '1', 'style' => 'solid', 'radius' => '0', 'color' => '#ccc', 'top' => 'on', 'right' => 'on', 'bottom' => 'on', 'left' => 'on'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Input border',
                    'tooltip' => 'Adjust the border style of input fields.',
                    'type' => 'border-style',
                    'group' => 'style',
                ],
                'input-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Input shadow',
                    'tooltip' => 'Adjust the shadow of input fields.',
                    'type' => 'shadow',
                    'group' => 'style',
                ],
                'end-inputs-default' => ['type' => 'section-end'],
                'start-inputs-hover' => ['type' => 'section-start', 'section' => 'inputs-hover'],
                'input-hover-inherit' => ['value' => 'on', 'label' => 'Inherit default style', 'tooltip' => 'Use the same style as for default state.', 'type' => 'checkbox', 'group' => 'style'],
                'input-hover-text-style' => [
                    'value' => ['family' => '', 'size' => '15', 'color' => '#444', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'left'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Input text',
                    'tooltip' => 'Adjust the text style of hovered input fields.',
                    'type' => 'text-style',
                    'visible' => ['input-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'input-hover-background-style' => [
                    'value' => ['image' => '', 'size' => 'auto', 'horizontal-position' => 'left', 'vertical-position' => 'top', 'repeat' => 'repeat', 'color' => '#fff', 'color2' => '', 'gradient' => 'no'],
                    'caption' => [
                        'image' => 'Image URL',
                        'size' => 'Size',
                        'horizontal-position' => 'Horizontal position',
                        'vertical-position' => 'Verical position',
                        'repeat' => 'Repeat',
                        'color' => 'Color',
                        'color2' => 'Second color',
                        'gradient' => 'Gradient',
                    ],
                    'label' => 'Input background',
                    'tooltip' => 'Adjust the background of hovered input fields.',
                    'type' => 'background-style',
                    'visible' => ['input-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'input-hover-border-style' => [
                    'value' => ['width' => '1', 'style' => 'solid', 'radius' => '0', 'color' => '#ccc', 'top' => 'on', 'right' => 'on', 'bottom' => 'on', 'left' => 'on'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Input border',
                    'tooltip' => 'Adjust the border style of hovered input fields.',
                    'type' => 'border-style',
                    'visible' => ['input-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'input-hover-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Input shadow',
                    'tooltip' => 'Adjust the shadow of hovered input fields.',
                    'type' => 'shadow',
                    'visible' => ['input-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'end-inputs-hover' => ['type' => 'section-end'],
                'start-inputs-focus' => ['type' => 'section-start', 'section' => 'inputs-focus'],
                'input-focus-inherit' => ['value' => 'on', 'label' => 'Inherit default style', 'tooltip' => 'Use the same style as for default state.', 'type' => 'checkbox', 'group' => 'style'],
                'input-focus-text-style' => [
                    'value' => ['family' => '', 'size' => '15', 'color' => '#444', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'left'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Input text',
                    'tooltip' => 'Adjust the text style of focused input fields.',
                    'type' => 'text-style',
                    'visible' => ['input-focus-inherit' => ['off']],
                    'group' => 'style',
                ],
                'input-focus-background-style' => [
                    'value' => ['image' => '', 'size' => 'auto', 'horizontal-position' => 'left', 'vertical-position' => 'top', 'repeat' => 'repeat', 'color' => '#fff', 'color2' => '', 'gradient' => 'no'],
                    'caption' => [
                        'image' => 'Image URL',
                        'size' => 'Size',
                        'horizontal-position' => 'Horizontal position',
                        'vertical-position' => 'Verical position',
                        'repeat' => 'Repeat',
                        'color' => 'Color',
                        'color2' => 'Second color',
                        'gradient' => 'Gradient',
                    ],
                    'label' => 'Input background',
                    'tooltip' => 'Adjust the background of focused input fields.',
                    'type' => 'background-style',
                    'visible' => ['input-focus-inherit' => ['off']],
                    'group' => 'style',
                ],
                'input-focus-border-style' => [
                    'value' => ['width' => '1', 'style' => 'solid', 'radius' => '0', 'color' => '#ccc', 'top' => 'on', 'right' => 'on', 'bottom' => 'on', 'left' => 'on'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Input border',
                    'tooltip' => 'Adjust the border style of focused input fields.',
                    'type' => 'border-style',
                    'visible' => ['input-focus-inherit' => ['off']],
                    'group' => 'style',
                ],
                'input-focus-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Input shadow',
                    'tooltip' => 'Adjust the shadow of focused input fields.',
                    'type' => 'shadow',
                    'visible' => ['input-focus-inherit' => ['off']],
                    'group' => 'style',
                ],
                'end-inputs-focus' => ['type' => 'section-end'],
                'hr-5' => ['type' => 'hr'],
                'checkbox-radio-style' => [
                    'value' => ['position' => 'left', 'size' => 'medium', 'align' => 'left', 'layout' => '1'],
                    'caption' => ['position' => 'Position', 'size' => 'Size', 'align' => 'Alignment', 'layout' => 'Layout'],
                    'label' => 'Checkbox and radio style',
                    'tooltip' => 'Choose how to display checkbox and radio button fields and their captions.',
                    'type' => 'checkbox-radio-style',
                    'group' => 'style',
                ],
                'checkbox-view' => [
                    'value' => 'classic',
                    'options' => [
                        'classic',
                        'fa-check',
                        'square',
                        'tgl',
                        'inverted',
                    ],
                    'label' => 'Checkbox view',
                    'tooltip' => 'Choose the checkbox style.',
                    'type' => 'checkbox-view',
                    'group' => 'style',
                ],
                'radio-view' => [
                    'value' => 'classic',
                    'options' => ['classic', 'fa-check', 'dot'],
                    'label' => 'Radio button view',
                    'tooltip' => 'Choose the radio button style.',
                    'type' => 'radio-view',
                    'group' => 'style',
                ],
                'checkbox-radio-sections' => [
                    'type' => 'sections',
                    'sections' => [
                        'checkbox-radio-unchecked' => ['label' => 'Unchecked', 'icon' => 'far fa-square'],
                        'checkbox-radio-checked' => ['label' => 'Checked', 'icon' => 'far fa-check-square'],
                    ],
                ],
                'start-checkbox-radio-unchecked' => ['type' => 'section-start', 'section' => 'checkbox-radio-unchecked'],
                'checkbox-radio-unchecked-color' => [
                    'value' => ['color1' => '#ccc', 'color2' => '#fff', 'color3' => '#444'],
                    'label' => 'Checkbox and radio colors',
                    'tooltip' => 'Adjust colors of checkboxes and radio buttons.',
                    'caption' => ['color1' => 'Border', 'color2' => 'Background', 'color3' => 'Mark'],
                    'type' => 'three-colors',
                    'group' => 'style',
                ],
                'end-checkbox-radio-unchecked' => ['type' => 'section-end'],
                'start-checkbox-radio-checked' => ['type' => 'section-start', 'section' => 'checkbox-radio-checked'],
                'checkbox-radio-checked-inherit' => ['value' => 'on', 'label' => 'Inherit colors', 'tooltip' => 'Use the same colors as for unchecked state.', 'type' => 'checkbox', 'group' => 'style'],
                'checkbox-radio-checked-color' => [
                    'value' => ['color1' => '#ccc', 'color2' => '#fff', 'color3' => '#444'],
                    'label' => 'Checkbox and radio colors',
                    'tooltip' => 'Adjust colors of checkboxes and radio buttons.',
                    'caption' => ['color1' => 'Border', 'color2' => 'Background', 'color3' => 'Mark'],
                    'type' => 'three-colors',
                    'visible' => ['checkbox-radio-checked-inherit' => ['off']],
                    'group' => 'style',
                ],
                'end-checkbox-radio-checked' => ['type' => 'section-end'],
                'hr-6' => ['type' => 'hr'],
                'imageselect-style' => [
                    'value' => ['align' => 'left', 'effect' => 'none'],
                    'caption' => ['align' => 'Alignment', 'effect' => 'Effect'],
                    'label' => 'Image Select style',
                    'tooltip' => 'Adjust image alignment and effect.',
                    'type' => 'imageselect-style',
                    'options' => ['none' => 'None', 'grayscale' => 'Grayscale'],
                    'group' => 'style',
                ],
                'imageselect-text-style' => [
                    'value' => ['family' => '', 'size' => '15', 'color' => '#444', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'left'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Image label text',
                    'tooltip' => 'Adjust the text style of image label.',
                    'type' => 'text-style',
                    'group' => 'style',
                ],
                'imageselects-style-sections' => [
                    'type' => 'sections',
                    'sections' => [
                        'imageselects-default' => ['label' => 'Default', 'icon' => 'fas fa-globe'],
                        'imageselects-hover' => ['label' => 'Hover', 'icon' => 'far fa-hand-pointer'],
                        'imageselects-selected' => ['label' => 'Selected', 'icon' => 'far fa-check-square'],
                    ],
                ],
                'start-imageselects-default' => ['type' => 'section-start', 'section' => 'imageselects-default'],
                'imageselect-border-style' => [
                    'value' => ['width' => '1', 'style' => 'solid', 'radius' => '0', 'color' => '#ccc', 'top' => 'on', 'right' => 'on', 'bottom' => 'on', 'left' => 'on'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Image border',
                    'tooltip' => 'Adjust the border style of images.',
                    'type' => 'border-style',
                    'group' => 'style',
                ],
                'imageselect-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Image shadow',
                    'tooltip' => 'Adjust the shadow of images.',
                    'type' => 'shadow',
                    'group' => 'style',
                ],
                'end-imageselects-default' => ['type' => 'section-end'],
                'start-imageselects-hover' => ['type' => 'section-start', 'section' => 'imageselects-hover'],
                'imageselect-hover-inherit' => ['value' => 'on', 'label' => 'Inherit default style', 'tooltip' => 'Use the same style as for default state.', 'type' => 'checkbox', 'group' => 'style'],
                'imageselect-hover-border-style' => [
                    'value' => ['width' => '1', 'style' => 'solid', 'radius' => '0', 'color' => '#ccc', 'top' => 'on', 'right' => 'on', 'bottom' => 'on', 'left' => 'on'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Image border',
                    'tooltip' => 'Adjust the border style of hovered images.',
                    'type' => 'border-style',
                    'visible' => ['imageselect-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'imageselect-hover-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Image shadow',
                    'tooltip' => 'Adjust the shadow of hovered images.',
                    'type' => 'shadow',
                    'visible' => ['imageselect-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'end-imageselects-hover' => ['type' => 'section-end'],
                'start-imageselects-selected' => ['type' => 'section-start', 'section' => 'imageselects-selected'],
                'imageselect-selected-inherit' => ['value' => 'on', 'label' => 'Inherit default style', 'tooltip' => 'Use the same style as for default state.', 'type' => 'checkbox', 'group' => 'style'],
                'imageselect-selected-border-style' => [
                    'value' => ['width' => '1', 'style' => 'solid', 'radius' => '0', 'color' => '#ccc', 'top' => 'on', 'right' => 'on', 'bottom' => 'on', 'left' => 'on'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Image border',
                    'tooltip' => 'Adjust the border style of selected images.',
                    'type' => 'border-style',
                    'visible' => ['imageselect-selected-inherit' => ['off']],
                    'group' => 'style',
                ],
                'imageselect-selected-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Image shadow',
                    'tooltip' => 'Adjust the shadow of selected images.',
                    'type' => 'shadow',
                    'visible' => ['imageselect-selected-inherit' => ['off']],
                    'group' => 'style',
                ],
                'imageselect-selected-scale' => ['value' => 'on', 'label' => 'Zoom selected image', 'tooltip' => 'Zoom selected image.', 'type' => 'checkbox', 'group' => 'style'],
                'end-imageselects-selected' => ['type' => 'section-end'],
                'hr-7' => ['type' => 'hr'],
                'multiselect-style' => [
                    'value' => ['align' => 'left', 'height' => '120', 'hover-background' => '#26B99A', 'hover-color' => '#ffffff', 'selected-background' => '#169F85', 'selected-color' => '#ffffff'],
                    'caption' => ['align' => 'Alignment', 'height' => 'Height', 'hover-color' => 'Hover colors', 'selected-color' => 'Selected colors'],
                    'label' => 'Multiselect style',
                    'tooltip' => 'Choose how to display multiselect options.',
                    'type' => 'multiselect-style',
                    'group' => 'style',
                ],
                'hr-8' => ['type' => 'hr'],
                'tile-style' => [
                    'value' => ['size' => 'medium', 'width' => 'default', 'position' => 'left', 'layout' => 'inline'],
                    'caption' => ['size' => 'Size', 'width' => 'Width', 'position' => 'Position', 'layout' => 'Layout'],
                    'label' => 'Tile style',
                    'tooltip' => 'Adjust the tile style.',
                    'type' => 'global-tile-style',
                    'group' => 'style',
                ],
                'tile-style-sections' => [
                    'type' => 'sections',
                    'sections' => [
                        'tiles-default' => ['label' => 'Default', 'icon' => 'fas fa-globe'],
                        'tiles-hover' => ['label' => 'Hover', 'icon' => 'far fa-hand-pointer'],
                        'tiles-active' => ['label' => 'Selected', 'icon' => 'far fa-check-square'],
                    ],
                ],
                'start-tiles-default' => ['type' => 'section-start', 'section' => 'tiles-default'],
                'tile-text-style' => [
                    'value' => ['family' => '', 'size' => '15', 'color' => '#444', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'center'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Tile text',
                    'tooltip' => 'Adjust the text style of tiles.',
                    'type' => 'text-style',
                    'group' => 'style',
                ],
                'tile-background-style' => [
                    'value' => ['image' => '', 'size' => 'auto', 'horizontal-position' => 'left', 'vertical-position' => 'top', 'repeat' => 'repeat', 'color' => '#ffffff', 'color2' => '', 'gradient' => 'no'],
                    'caption' => [
                        'image' => 'Image URL',
                        'size' => 'Size',
                        'horizontal-position' => 'Horizontal position',
                        'vertical-position' => 'Vertical position',
                        'repeat' => 'Repeat',
                        'color' => 'Color',
                        'color2' => 'Second color',
                        'gradient' => 'Gradient',
                    ],
                    'label' => 'Tile background',
                    'tooltip' => 'Adjust the background of tiles.',
                    'type' => 'background-style',
                    'group' => 'style',
                ],
                'tile-border-style' => [
                    'value' => ['width' => '1', 'style' => 'solid', 'radius' => '0', 'color' => '#ccc', 'top' => 'on', 'right' => 'on', 'bottom' => 'on', 'left' => 'on'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Tile border',
                    'tooltip' => 'Adjust the border style of tiles.',
                    'type' => 'border-style',
                    'group' => 'style',
                ],
                'tile-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Tile shadow',
                    'tooltip' => 'Adjust the shadow of tile.',
                    'type' => 'shadow',
                    'group' => 'style',
                ],
                'end-tiles-default' => ['type' => 'section-end'],
                'start-tiles-hover' => ['type' => 'section-start', 'section' => 'tiles-hover'],
                'tile-hover-inherit' => ['value' => 'on', 'label' => 'Inherit default style', 'tooltip' => 'Use the same style as for default state.', 'type' => 'checkbox', 'group' => 'style'],
                'tile-hover-text-style' => [
                    'value' => ['family' => '', 'size' => '15', 'color' => '#444', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'center'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Tile text',
                    'tooltip' => 'Adjust the text style of hovered tiles.',
                    'type' => 'text-style',
                    'visible' => ['tile-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'tile-hover-background-style' => [
                    'value' => ['image' => '', 'size' => 'auto', 'horizontal-position' => 'left', 'vertical-position' => 'top', 'repeat' => 'repeat', 'color' => '#ffffff', 'color2' => '', 'gradient' => 'no'],
                    'caption' => [
                        'image' => 'Image URL',
                        'size' => 'Size',
                        'horizontal-position' => 'Horizontal position',
                        'vertical-position' => 'Verical position',
                        'repeat' => 'Repeat',
                        'color' => 'Color',
                        'color2' => 'Second color',
                        'gradient' => 'Gradient',
                    ],
                    'label' => 'Tile background',
                    'tooltip' => 'Adjust the background of hovered tiles.',
                    'type' => 'background-style',
                    'visible' => ['tile-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'tile-hover-border-style' => [
                    'value' => ['width' => '1', 'style' => 'solid', 'radius' => '0', 'color' => '#169F85', 'top' => 'on', 'right' => 'on', 'bottom' => 'on', 'left' => 'on'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Tile border',
                    'tooltip' => 'Adjust the border style of hovered tiles.',
                    'type' => 'border-style',
                    'visible' => ['tile-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'tile-hover-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Tile shadow',
                    'tooltip' => 'Adjust the shadow of hovered tiles.',
                    'type' => 'shadow',
                    'visible' => ['tile-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'end-tiles-hover' => ['type' => 'section-end'],
                'start-tiles-active' => ['type' => 'section-start', 'section' => 'tiles-active'],
                'tile-selected-inherit' => ['value' => 'on', 'label' => 'Inherit default style', 'tooltip' => 'Use the same style as for default state.', 'type' => 'checkbox', 'group' => 'style'],
                'tile-selected-text-style' => [
                    'value' => ['family' => '', 'size' => '15', 'color' => '#444', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'center'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Tile text',
                    'tooltip' => 'Adjust the text style of selected tiles.',
                    'type' => 'text-style',
                    'visible' => ['tile-selected-inherit' => ['off']],
                    'group' => 'style',
                ],
                'tile-selected-background-style' => [
                    'value' => ['image' => '', 'size' => 'auto', 'horizontal-position' => 'left', 'vertical-position' => 'top', 'repeat' => 'repeat', 'color' => '#ffffff', 'color2' => '', 'gradient' => 'no'],
                    'caption' => [
                        'image' => 'Image URL',
                        'size' => 'Size',
                        'horizontal-position' => 'Horizontal position',
                        'vertical-position' => 'Verical position',
                        'repeat' => 'Repeat',
                        'color' => 'Color',
                        'color2' => 'Second color',
                        'gradient' => 'Gradient',
                    ],
                    'label' => 'Tile background',
                    'tooltip' => 'Adjust the background of selected tiles.',
                    'type' => 'background-style',
                    'visible' => ['tile-selected-inherit' => ['off']],
                    'group' => 'style',
                ],
                'tile-selected-border-style' => [
                    'value' => ['width' => '1', 'style' => 'solid', 'radius' => '0', 'color' => '#169F85', 'top' => 'on', 'right' => 'on', 'bottom' => 'on', 'left' => 'on'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Tile border',
                    'tooltip' => 'Adjust the border style of selected tiles.',
                    'type' => 'border-style',
                    'visible' => ['tile-selected-inherit' => ['off']],
                    'group' => 'style',
                ],
                'tile-selected-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Tile shadow',
                    'tooltip' => 'Adjust the shadow of selected tiles.',
                    'type' => 'shadow',
                    'visible' => ['tile-selected-inherit' => ['off']],
                    'group' => 'style',
                ],
                'tile-selected-transform' => [
                    'value' => 'zoom-in',
                    'label' => 'Transform',
                    'tooltip' => 'Adjust the transform of selected tiles.',
                    'type' => 'radio-bar',
                    'options' => ['none' => 'None', 'zoom-in' => 'Zoom In', 'zoom-out' => 'Zoom Out', 'shift-down' => 'Shift Down'],
                    'group' => 'style',
                ],
                'end-tiles-active' => ['type' => 'section-end'],
                'hr-10' => ['type' => 'hr'],
                'rangeslider-skin' => [
                    'value' => 'flat',
                    'label' => 'Range slider skin',
                    'tooltip' => 'Select the skin of range slider.',
                    'type' => 'select',
                    'options' => ['flat' => 'Flat', 'sharp' => 'Sharp', 'round' => 'Round'],
                    'group' => 'style',
                ],
                'rangeslider-color' => [
                    'value' => ['color1' => '#e8e8e8', 'color2' => '#888888', 'color3' => '#26B99A', 'color4' => '#169F85', 'color5' => '#ffffff'],
                    'label' => 'Range slider colors',
                    'tooltip' => 'Adjust colors of range slider.',
                    'caption' => ['color1' => 'Main', 'color2' => 'Min/max text', 'color3' => 'Selected', 'color4' => 'Handle', 'color5' => 'Tooltip text'],
                    'type' => 'five-colors',
                    'group' => 'style',
                ],
                'end-inputs' => ['type' => 'section-end'],
                'start-buttons' => ['type' => 'section-start', 'section' => 'buttons'],
                'button-style' => [
                    'value' => ['size' => 'medium', 'width' => 'default', 'position' => 'center'],
                    'caption' => ['size' => 'Size', 'width' => 'Width', 'position' => 'Position'],
                    'label' => 'Button style',
                    'tooltip' => 'Adjust the button size and position.',
                    'type' => 'global-button-style',
                    'group' => 'style',
                ],
                'button-style-sections' => [
                    'type' => 'sections',
                    'sections' => [
                        'buttons-default' => ['label' => 'Default', 'icon' => 'fas fa-globe'],
                        'buttons-hover' => ['label' => 'Hover', 'icon' => 'far fa-hand-pointer'],
                        'buttons-active' => ['label' => 'Active', 'icon' => 'far fa-paper-plane'],
                    ],
                ],
                'start-buttons-default' => ['type' => 'section-start', 'section' => 'buttons-default'],
                'button-text-style' => [
                    'value' => ['family' => '', 'size' => '15', 'color' => '#fff', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'center'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Button text',
                    'tooltip' => 'Adjust the text style of buttons.',
                    'type' => 'text-style',
                    'group' => 'style',
                ],
                'button-background-style' => [
                    'value' => ['image' => '', 'size' => 'auto', 'horizontal-position' => 'left', 'vertical-position' => 'top', 'repeat' => 'repeat', 'color' => '#26B99A', 'color2' => '', 'gradient' => 'no'],
                    'caption' => [
                        'image' => 'Image URL',
                        'size' => 'Size',
                        'horizontal-position' => 'Horizontal position',
                        'vertical-position' => 'Verical position',
                        'repeat' => 'Repeat',
                        'color' => 'Color',
                        'color2' => 'Second color',
                        'gradient' => 'Gradient',
                    ],
                    'label' => 'Button background',
                    'tooltip' => 'Adjust the background of buttons.',
                    'type' => 'background-style',
                    'group' => 'style',
                ],
                'button-border-style' => [
                    'value' => ['width' => '1', 'style' => 'solid', 'radius' => '0', 'color' => '#169F85', 'top' => 'on', 'right' => 'on', 'bottom' => 'on', 'left' => 'on'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Button border',
                    'tooltip' => 'Adjust the border style of buttons.',
                    'type' => 'border-style',
                    'group' => 'style',
                ],
                'button-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Button shadow',
                    'tooltip' => 'Adjust the shadow of button.',
                    'type' => 'shadow',
                    'group' => 'style',
                ],
                'end-buttons-default' => ['type' => 'section-end'],
                'start-buttons-hover' => ['type' => 'section-start', 'section' => 'buttons-hover'],
                'button-hover-inherit' => ['value' => 'on', 'label' => 'Inherit default style', 'tooltip' => 'Use the same style as for default state.', 'type' => 'checkbox', 'group' => 'style'],
                'button-hover-text-style' => [
                    'value' => ['family' => '', 'size' => '15', 'color' => '#fff', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'center'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Button text',
                    'tooltip' => 'Adjust the text style of hovered buttons.',
                    'type' => 'text-style',
                    'visible' => ['button-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'button-hover-background-style' => [
                    'value' => ['image' => '', 'size' => 'auto', 'horizontal-position' => 'left', 'vertical-position' => 'top', 'repeat' => 'repeat', 'color' => '#169F85', 'color2' => '', 'gradient' => 'no'],
                    'caption' => [
                        'image' => 'Image URL',
                        'size' => 'Size',
                        'horizontal-position' => 'Horizontal position',
                        'vertical-position' => 'Verical position',
                        'repeat' => 'Repeat',
                        'color' => 'Color',
                        'color2' => 'Second color',
                        'gradient' => 'Gradient',
                    ],
                    'label' => 'Button background',
                    'tooltip' => 'Adjust the background of hovered buttons.',
                    'type' => 'background-style',
                    'visible' => ['button-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'button-hover-border-style' => [
                    'value' => ['width' => '1', 'style' => 'solid', 'radius' => '0', 'color' => '#169F85', 'top' => 'on', 'right' => 'on', 'bottom' => 'on', 'left' => 'on'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Button border',
                    'tooltip' => 'Adjust the border style of hovered buttons.',
                    'type' => 'border-style',
                    'visible' => ['button-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'button-hover-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Button shadow',
                    'tooltip' => 'Adjust the shadow of hovered buttons.',
                    'type' => 'shadow',
                    'visible' => ['button-hover-inherit' => ['off']],
                    'group' => 'style',
                ],
                'end-buttons-hover' => ['type' => 'section-end'],
                'start-buttons-active' => ['type' => 'section-start', 'section' => 'buttons-active'],
                'button-active-inherit' => ['value' => 'on', 'label' => 'Inherit default style', 'tooltip' => 'Use the same style as for default state.', 'type' => 'checkbox', 'group' => 'style'],
                'button-active-text-style' => [
                    'value' => ['family' => '', 'size' => '15', 'color' => '#fff', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'center'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Button text',
                    'tooltip' => 'Adjust the text style of clicked buttons.',
                    'type' => 'text-style',
                    'visible' => ['button-active-inherit' => ['off']],
                    'group' => 'style',
                ],
                'button-active-background-style' => [
                    'value' => ['image' => '', 'size' => 'auto', 'horizontal-position' => 'left', 'vertical-position' => 'top', 'repeat' => 'repeat', 'color' => '#169F85', 'color2' => '', 'gradient' => 'no'],
                    'caption' => [
                        'image' => 'Image URL',
                        'size' => 'Size',
                        'horizontal-position' => 'Horizontal position',
                        'vertical-position' => 'Verical position',
                        'repeat' => 'Repeat',
                        'color' => 'Color',
                        'color2' => 'Second color',
                        'gradient' => 'Gradient',
                    ],
                    'label' => 'Button background',
                    'tooltip' => 'Adjust the background of clicked buttons.',
                    'type' => 'background-style',
                    'visible' => ['button-active-inherit' => ['off']],
                    'group' => 'style',
                ],
                'button-active-border-style' => [
                    'value' => ['width' => '1', 'style' => 'solid', 'radius' => '0', 'color' => '#169F85', 'top' => 'on', 'right' => 'on', 'bottom' => 'on', 'left' => 'on'],
                    'caption' => ['width' => 'Width', 'style' => 'Style', 'radius' => 'Radius', 'color' => 'Color', 'border' => 'Border'],
                    'label' => 'Button border',
                    'tooltip' => 'Adjust the border style of clicked buttons.',
                    'type' => 'border-style',
                    'visible' => ['button-active-inherit' => ['off']],
                    'group' => 'style',
                ],
                'button-active-shadow' => [
                    'value' => ['style' => 'regular', 'size' => '', 'color' => '#444'],
                    'caption' => ['style' => 'Style', 'size' => 'Size', 'color' => 'Color'],
                    'label' => 'Button shadow',
                    'tooltip' => 'Adjust the shadow of clicked buttons.',
                    'type' => 'shadow',
                    'visible' => ['button-active-inherit' => ['off']],
                    'group' => 'style',
                ],
                'button-active-transform' => [
                    'value' => 'zoom-out',
                    'label' => 'Transform',
                    'tooltip' => 'Adjust the transform of clicked buttons.',
                    'type' => 'radio-bar',
                    'options' => ['zoom-in' => 'Zoom In', 'zoom-out' => 'Zoom Out', 'shift-down' => 'Shift Down'],
                    'group' => 'style',
                ],
                'end-buttons-active' => ['type' => 'section-end'],
                'end-buttons' => ['type' => 'section-end'],
                'start-errors' => ['type' => 'section-start', 'section' => 'errors'],
                'error-background-style' => [
                    'value' => ['image' => '', 'size' => 'auto', 'horizontal-position' => 'left', 'vertical-position' => 'top', 'repeat' => 'repeat', 'color' => '#d9534f', 'color2' => '', 'gradient' => 'no'],
                    'caption' => [
                        'image' => 'Image URL',
                        'size' => 'Size',
                        'horizontal-position' => 'Horizontal position',
                        'vertical-position' => 'Verical position',
                        'repeat' => 'Repeat',
                        'color' => 'Color',
                        'color2' => 'Second color',
                        'gradient' => 'Gradient',
                    ],
                    'label' => 'Bubble background',
                    'tooltip' => 'Adjust the background of error bubbles.',
                    'type' => 'background-style',
                    'group' => 'style',
                ],
                'error-text-style' => [
                    'value' => ['family' => '', 'size' => '15', 'color' => '#fff', 'bold' => 'off', 'italic' => 'off', 'underline' => 'off', 'align' => 'left'],
                    'caption' => [
                        'family' => 'Font family',
                        'size' => 'Size',
                        'color' => 'Color',
                        'style' => 'Style',
                        'align' => 'Alignment',
                    ],
                    'label' => 'Error text style',
                    'tooltip' => 'Adjust the text style of errors.',
                    'type' => 'text-style',
                    'group' => 'style',
                ],
                'end-errors' => ['type' => 'section-end'],
                'start-progress' => ['type' => 'section-start', 'section' => 'progress'],
                'progress-enable' => [
                    'value' => 'off',
                    'label' => 'Enable progress bar',
                    'tooltip' => 'If your form the form has several pages/steps, it is recommended to display progress bar for better user experience.',
                    'type' => 'checkbox',
                ],
                'progress-type' => [
                    'value' => 'progress-1',
                    'label' => 'Progress style',
                    'tooltip' => 'Select the general view of progress bar.',
                    'type' => 'select-image',
                    'options' => ['progress-1' => $this->plugins_url . '/images/progress-1.png', 'progress-2' => $this->plugins_url . '/images/progress-2.png'],
                    'width' => 350,
                    'height' => 90,
                    'visible' => ['progress-enable' => ['on']],
                    'group' => 'style',
                ],
                'progress-color' => [
                    'value' => ['color1' => '#e0e0e0', 'color2' => '#26B99A', 'color3' => '#FFFFFF', 'color4' => '#444'],
                    'label' => 'Colors',
                    'tooltip' => 'Adjust colors of progress bar.',
                    'caption' => ['color1' => 'Passive background', 'color2' => 'Active background', 'color3' => 'Page number (or %)', 'color4' => 'Page name'],
                    'type' => 'four-colors',
                    'visible' => ['progress-enable' => ['on']],
                    'group' => 'style',
                ],
                'progress-striped' => [
                    'value' => 'off',
                    'label' => 'Double-tone stripes',
                    'tooltip' => 'Add double-tone diagonal stripes to progress bar.',
                    'type' => 'checkbox',
                    'visible' => ['progress-enable' => ['on']],
                    'group' => 'style',
                ],
                'progress-label-enable' => [
                    'value' => 'off',
                    'label' => 'Show page name',
                    'tooltip' => 'Show page label.',
                    'type' => 'checkbox',
                    'visible' => ['progress-enable' => ['on']],
                    'group' => 'style',
                ],
                'progress-confirmation-enable' => [
                    'value' => 'on',
                    'label' => 'Include confirmation page',
                    'tooltip' => 'Consider Confirmation page as part of total pages and include it into progress bar.',
                    'type' => 'checkbox',
                    'visible' => ['progress-enable' => ['on']],
                ],
                'progress-position' => [
                    'value' => 'inside',
                    'label' => 'Position',
                    'tooltip' => 'Select the position of progress bar. It can be inside or outside of main form wrapper.',
                    'type' => 'select',
                    'options' => ['inside' => 'Inside', 'outside' => 'Outside'],
                    'visible' => ['progress-enable' => ['on']],
                    'group' => 'style',
                ],
                'end-progress' => ['type' => 'section-end'],

                /* 'confirmation-tab' => ['type' => 'tab', 'value' => 'confirmation', 'label' => 'Confirmations'],
                'confirmations' => [
                    'type' => 'confirmations',
                    'values' => [],
                    'label' => 'Confirmations',
                    'message' => 'By default after successfull form submission the Confirmation Page is displayed. You can customize confirmation and use conditional logic. If several confirmations match form conditions, the first one (higher priority) will be applied. Sort confirmations (drag and drop) to set priority.',
                ], */

                'double-tab' => ['type' => 'tab', 'value' => 'double', 'label' => 'Double Opt-In'],
                'double-enable' => [
                    'value' => 'off',
                    'label' => 'Enable',
                    'tooltip' => 'Aktivieren, um Benutzer die übermittelten Daten bestätigen zu lassen. Wenn aktiviert, sendet die App eine E-Mail mit einem Bestätigungslink an die vom Benutzer angegebene E-Mail-Adresse. Wenn der Bestätigungslink angeklickt wird, wird der entsprechende Datensatz als "bestätigt" markiert. Außerdem werden alle Benachrichtigungen und Integrationen nur dann ausgeführt, wenn die Daten vom Benutzer bestätigt wurden. E-Mails werden nur an diese Adresse gesendet, wenn Sie SMTP-Einstellungen konfiguriert haben.',
                    'type' => 'checkbox',
                ],
                'double-email-recipient' => [
                    'value' => '',
                    'label' => 'Recipient',
                    'tooltip' => 'Die E-Mail-Adresse an die der Bestätigungslink gesendet werden soll. Für den E-Mail-Versand muss ein SMTP in den globalen Einstellungen konfiguriert sein.',
                    'type' => 'email-receipment-element-selector'
                ],
                'double-email-subject' => [
                    'value' => 'E-Mail-Adresse bestätigen',
                    'label' => 'Subject',
                    'tooltip' => 'Der Betreff der E-Mail. Für den E-Mail-Versand muss ein SMTP in den globalen Einstellungen konfiguriert sein.',
                    'type' => 'text',
                ],
                'double-email-message' => [
                    'value' => 'Vielen Dank! {{confirmation-url}}',
                    'label' => 'Message',
                    'tooltip' => sprintf('Der Inhalt der Nachricht. Die Variable %s{{confirmation-url}}%s muss im Nachrichtentext vorkommen. Für den E-Mail-Versand muss ein SMTP in den globalen Einstellungen konfiguriert sein.', '<code>', '</code>'),
                    'type' => 'email-autorespond-message',
                ],
                /*
                'double-from' => [
                    'value' => ['email' => '{{global-from-email}}', 'name' => '{{global-from-name}}'],
                    'label' => 'From',
                    'tooltip' => 'Sets the "From" address and name. The email address and name set here will be shown as the sender of the email.',
                    'type' => 'from',
                ],
                'double-message' => [
                    'value' => '<h4 style="text-align: center;">Thank you!</h4><p style="text-align: center;">Your email address successfully confirmed.</p>',
                    'label' => 'Thanksgiving message',
                    'tooltip' => 'This message is displayed when users successfully confirmed their e-mail addresses.',
                    'type' => 'html',
                ],
                'double-url' => [
                    'value' => '',
                    'label' => 'Thanksgiving URL',
                    'tooltip' => 'This is alternate way of thanksgiving message. After confirmation users are redirected to this URL.',
                    'type' => 'text',
                ],
                 */
                /*
                'notification-tab' => ['type' => 'tab', 'value' => 'notification', 'label' => 'Notifications'],
                'notifications' => [
                    'type' => 'notifications',
                    'values' => [],
                    'label' => 'Notifications',
                    'message' => 'After successful form submission the notification, welcome, thanksgiving or whatever email can be sent. You can customize these emails and use conditional logic.',
                ],
                 */


                'integration-tab' => ['type' => 'tab', 'value' => 'integration', 'label' => 'Integrations'],
                /*
                'integrations' => [
                    'type' => 'integrations',
                    'values' => [],
                    'label' => 'Integrations',
                    'message' => 'After successful form submission its data can be sent to 3rd party services (such as MailChimp, AWeber, GetResponse, etc.). You can configure integrations and use conditional logic. If you do not see your marketing/CRM provider, make sure that you enabled appropriate integration module on Advanced Settings page.',
                ],
                 */




                'webhook-integration-enable' => [
                    'value' => 'off',
                    'label' => 'Enable webhook-integration',
                    # 'tooltip' => 'Activate this option if you want to enable sessions for the form. Session allows to keep non-completed form data, so user can continue form filling when come back.',
                    'type' => 'checkbox',
                ],
                'webhook-integration' => [
                    'value' => '',
                    'label' => 'webhook-integration url',
                    # 'tooltip' => 'Specify how many hours non-completed data are kept.',
                    'type' => 'text',
                    'visible' => ['webhook-integration-enable' => ['on']],
                ],

                'webhook-integration-security-enable' => [
                    'value' => 'off',
                    'label' => 'Enable webhook-integration-security',
                    # 'tooltip' => 'Activate this option if you want to enable sessions for the form. Session allows to keep non-completed form data, so user can continue form filling when come back.',
                    'type' => 'checkbox',
                    'visible' => ['webhook-integration-enable' => ['on']],
                ],
                'webhook-integration-security' => [
                    'value' => '',
                    'label' => 'webhook-integration-security token',
                    # 'tooltip' => 'Specify how many hours non-completed data are kept.',
                    'type' => 'text',
                    'visible' => ['webhook-integration-security-enable' => ['on']],
                ],

                'user-downloads-results-as-pdf' => [
                    'value' => 'off',
                    'label' => 'User downloads results as pdf',
                    # 'tooltip' => 'Activate this option if you want to enable sessions for the form. Session allows to keep non-completed form data, so user can continue form filling when come back.',
                    'type' => 'checkbox',
                ],
                'use-pupeteer' => [
                    'value' => 'off',
                    'label' => 'Use Browsershot to generate pdf?',
                    'type' => 'checkbox',
                ],
                'has-custom-pdf-filename' => [
                    'value' => 'off',
                    'label' => 'Can you specify the filename for PDF files?',
                    'type' => 'checkbox',
                ],
                'custom-pdf-filename' => [
                    'value' => '',
                    'label' => 'Custom file name',
                    'type' => 'xml-file-name',
                    'visible' => ['has-custom-pdf-filename' => ['on']],
                ],

                'email-on-form-submition-enable' => [
                    'value' => 'off',
                    'label' => 'Email on form submition',
                    'tooltip' => 'Für den E-Mail-Versand muss ein SMTP in den globalen Einstellungen konfiguriert sein.',
                    'type' => 'checkbox',
                ],
                'email-on-form-submition' => [
                    'value' => '',
                    'label' => 'Email to',
                    'tooltip' => 'Für den E-Mail-Versand muss ein SMTP in den globalen Einstellungen konfiguriert sein.',
                    'type' => 'email-list',
                    'visible' => ['email-on-form-submition-enable' => ['on']],
                ],
                'subject-of-email-on-form-submition' => [
                    'value' => '',
                    'label' => 'Subject',
                    'tooltip' => 'Für den E-Mail-Versand muss ein SMTP in den globalen Einstellungen konfiguriert sein.',
                    'type' => 'text-with-form-fields',
                    'visible' => ['email-on-form-submition-enable' => ['on']],
                    'isTextarea' => false,
                ],
                'email-on-form-submition-table-template' => [
                    'value' => '',
                    'label' => 'Table view',
                    # 'tooltip' => 'Specify how many hours non-completed data are kept.',
                    'type' => 'checkbox',
                    'visible' => ['email-on-form-submition-enable' => ['on']],
                ],
                'email-on-form-submition-pdf-attachment' => [
                    'value' => '',
                    'label' => 'Attach pdf on email',
                    'tooltip' => 'Für den E-Mail-Versand muss ein SMTP in den globalen Einstellungen konfiguriert sein.',
                    'type' => 'checkbox',
                    'visible' => ['email-on-form-submition-enable' => ['on']],
                ],




                'advanced-tab' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'advanced-sections' => [
                    'type' => 'sections',
                    'sections' => [
                        'math' => ['label' => 'Math Expressions', 'icon' => 'fas fa-plus'],
                        /*
                        'payment-gateways' => ['label' => 'Payment Gateways', 'icon' => 'fas fa-dollar-sign'],
                         */
                        'misc' => ['label' => 'Miscellaneous', 'icon' => 'fas fa-project-diagram'],
                    ],
                ],
                'start-math' => ['type' => 'section-start', 'section' => 'math'],
                'math-expressions' => ['type' => 'math-expressions', 'values' => [], 'label' => 'Math expressions', 'tooltip' => 'Create math expressions and use them along the form.'],
                'end-math' => ['type' => 'section-end'],
                /*
                'start-payment-gateways' => ['type' => 'section-start', 'section' => 'payment-gateways'],
                'payment-gateways' => [
                    'type' => 'payment-gateways',
                    'values' => [],
                    'label' => 'Payment gateways',
                    'message' => 'After successful form submission user can be requested to pay some amount via certain payment gateway. Customize payment gateways here. Then go to "Confirmations" tab and create confirmation of one of the following types: "Display Confirmation page and request payment", "Display Message and request payment" or "Request payment".',
                ],
                'end-payment-gateways' => ['type' => 'section-end'],
                 */
                'start-misc' => ['type' => 'section-start', 'section' => 'misc'],
                'misc-save-ip' => ['value' => 'off', 'label' => 'Save IP-address', 'tooltip' => 'Save user\'s IP-address in local database.', 'type' => 'checkbox'],
                'misc-save-user-agent' => ['value' => 'off', 'label' => 'Save User-Agent', 'tooltip' => 'Save user\'s User-Agent in local database.', 'type' => 'checkbox'],
                'misc-email-tech-info' => [
                    'value' => 'off',
                    'label' => 'Send Technical Info by email',
                    'tooltip' => 'Include Technical Info into "{{form-data}}" shortcode sent by email.',
                    'type' => 'checkbox',
                ],
                'misc-record-tech-info' => ['value' => 'off', 'label' => 'Show Technical Info on log record details', 'tooltip' => 'Show Technical Info on log record details.', 'type' => 'checkbox'],
                'personal-keys' => [
                    'values' => [],
                    'label' => 'Personal data key fields',
                    'tooltip' => 'Select fields which contains personal data keys. Usually it is an email field. WordPress uses this key to extract and handle personal data.',
                    'type' => 'personal-keys',
                ],
                'end-misc' => ['type' => 'section-end'],

                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'generate-xml-on-save' => [
                    'value' => 'off',
                    'label' => 'Generate xml on save',
                    'tooltip' => 'Generate xml on save',
                    'type' => 'checkbox'
                ],
                'transport-xml-via-ftp' => [
                    'value' => 'on',
                    'label' => 'Transport xml via FTP',
                    'tooltip' => 'Transport xml via FTP',
                    'type' => 'checkbox',
                    'visible' => ['generate-xml-on-save' => ['on']]
                ],
                'xml-webhook-integration-enable' => [
                    'value' => 'off',
                    'label' => 'XML an einen Webhook senden',
                    'type' => 'checkbox',
                    'visible' => ['generate-xml-on-save' => ['on']]
                ],
                'xml-webhook-integration' => [
                    'value' => '',
                    'label' => 'Webhook-URL',
                    'type' => 'text',
                    'visible' => ['xml-webhook-integration-enable' => ['on']],
                ],
                'xml-webhook-integration-security' => [
                    'value' => '',
                    'label' => 'Token',
                    'type' => 'text',
                    'visible' => ['xml-webhook-integration-enable' => ['on']],
                ],
                'xml-hide-hidden-fields' => [
                    'value' => 'off',
                    'label' => 'Only export visible fields',
                    'type' => 'checkbox',
                    'visible' => ['generate-xml-on-save' => ['on']]
                ],

                'has-xml-custom-file-name' => [
                    'value' => 'off',
                    'label' => 'Has xml custom file name',
                    'tooltip' => 'Has xml custom file name',
                    'type' => 'checkbox',
                    'visible' => ['generate-xml-on-save' => ['on']]
                ],
                'xml-custom-file-name' => [
                    'value' => '',
                    'label' => 'Xml custom file name',
                    'tooltip' => 'Xml custom file name',
                    'type' => 'xml-file-name',
                    'visible' => [
                        'has-xml-custom-file-name' => 'on',
                        'generate-xml-on-save' => 'on',
                    ]
                ],
                'xml-date-format' => [
                    'value' => '',
                    'label' => 'Formatierung des Datums (bspw. “d.m.Y”)',
                    'type' => 'text',
                    'visible' => ['generate-xml-on-save' => ['on']],
                ],
                'hide-xml-element-attr' => [
                    'value' => 'off',
                    'label' => 'Don’t output attributes in tags',
                    'tooltip' => 'Don’t output attributes in tags',
                    'type' => 'checkbox',
                    'visible' => ['generate-xml-on-save' => ['on']]
                ],
                'xml-field-names' => LeformService::$formPropertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$formPropertiesMetaCustomXmlFields,

                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'generate-csv-on-save' => [
                    'value' => 'off',
                    'label' => 'Generate csv on save',
                    'tooltip' => 'Generate csv on save',
                    'type' => 'checkbox'
                ],
                'transport-csv-via-ftp' => [
                    'value' => 'on',
                    'label' => 'Transport csv via FTP',
                    'tooltip' => 'Transport csv via FTP',
                    'type' => 'checkbox',
                    'visible' => ['generate-csv-on-save' => ['on']]
                ],
                'has-csv-custom-file-name' => [
                    'value' => 'off',
                    'label' => 'Has csv custom file name',
                    'tooltip' => 'Has csv custom file name',
                    'type' => 'checkbox',
                    'visible' => ['generate-csv-on-save' => ['on']]
                ],
                'csv-custom-file-name' => [
                    'value' => '',
                    'label' => 'Csv custom file name',
                    'tooltip' => 'Csv custom file name',
                    'type' => 'xml-file-name',
                    'visible' => [
                        'has-csv-custom-file-name' => ['on'],
                        'generate-csv-on-save' => ['on'],
                    ]
                ],
                'csv-file-separator' => [
                    'value' => ';',
                    'label' => 'Separator für Datenfelder (Spalten)',
                    'tooltip' => 'Separator für Datenfelder (Spalten)',
                    'type' => 'text',
                    'visible' => [
                        'generate-csv-on-save' => ['on'],
                    ]
                ],
                'csv-input-enclosure' => [
                    'value' => "'",
                    'label' => 'Input for enclosure of data',
                    'tooltip' => 'Input for enclosure of data',
                    'type' => 'text',
                    'visible' => [
                        'generate-csv-on-save' => ['on'],
                    ]
                ],
                'csv-include-header' => [
                    'value' => 'on',
                    'label' => 'Inlcude head row?', // Kopfzeile ausgeben?
                    'tooltip' => 'Inlcude head row?',
                    'type' => 'checkbox',
                    'visible' => ['generate-csv-on-save' => ['on']]
                ],
                'csv-saving-priority' => [
                    'value' => 'override',
                    'label' => 'Priority for saving',
                    'tooltip' => 'Priority for saving',
                    'type' => 'select',
                    'options' => $this->getTranslatedReportOverrideOptions(),
                    'visible' => ['generate-csv-on-save' => ['on']]
                ],
                'encoding-csv' => [
                    'value' => 'UTF-8',
                    'label' => '' . __("Encoding of CSV"),
                    'tooltip' => '' . __("Encoding of CSV"),
                    'type' => 'select',
                    'options' => [
                        'utf-8' => 'UTF-8',
                        'ansii' => 'ANSI'
                    ],
                    'visible' => ['generate-csv-on-save' => ['on']]
                ],

                'custom-report' => ['type' => 'tab', 'value' => 'custom-report', 'label' => 'Custom report'],
                'setup-custom-report' => [
                    'value' => 'off',
                    'label' => 'Setup custom report',
                    'tooltip' => 'Setup custom report',
                    'type' => 'checkbox'
                ],
                'report-content' => [
                    'value' => '',
                    'label' => 'Content of the report',
                    'tooltip' => 'Content of the report',
                    'type' => 'custom-report-textarea',
                    'visible' => ['setup-custom-report' => ['on']]
                ],
                'has-report-content-custom-file-name' => [
                    'value' => 'off',
                    'label' => 'Has report custom file name',
                    'tooltip' => 'Has report custom file name',
                    'type' => 'checkbox',
                    'visible' => ['setup-custom-report' => ['on']]
                ],
                'report-content-custom-file-name' => [
                    'value' => '',
                    'label' => 'Report custom file name',
                    'tooltip' => 'Report custom file name',
                    'type' => 'xml-file-name',
                    'visible' => [
                        'setup-custom-report' => ['on'],
                        'has-report-content-custom-file-name' => ['on'],
                    ]
                ],
                'report-content-extension' => [
                    'value' => '',
                    'label' => 'Report content extension',
                    'tooltip' => 'Report content extension',
                    'type' => 'text',
                    'visible' => ['setup-custom-report' => ['on']]
                ],
                'report-saving-priority' => [
                    'value' => 'override',
                    'label' => 'Priority for saving',
                    'tooltip' => 'Priority for saving',
                    'type' => 'select',
                    'options' => $this->getTranslatedReportOverrideOptions(),
                    'visible' => ['setup-custom-report' => ['on']]
                ],
            ],
            'page' => [
                'general' => ['type' => 'tab', 'value' => 'general', 'label' => 'General'],
                'name' => ['value' => 'Seiten', 'label' => 'Name', 'tooltip' => 'The name helps to identify the page.', 'type' => 'text'],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => '', 'type' => 'text'],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this page', 'hide' => 'Hide this page'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
            ],
            'page-confirmation' => [
                'general' => ['type' => 'tab', 'value' => 'general', 'label' => 'General'],
                'name' => ['value' => 'Confirmation', 'label' => 'Name', 'tooltip' => 'The name helps to identify the confirmation page.', 'type' => 'text'],
            ],
            'columns' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => ['value' => 'Untitled', 'label' => 'Name', 'tooltip' => 'The name will be shown throughout the plugin.', 'type' => 'text'],
                'widths' => [
                    'value' => '',
                    'label' => 'Column width',
                    'tooltip' => 'Specify the width of each column. The row is divided into 12 equal pieces. You can decide how many pieces related to each columns. If you want all columns to be in one row, make sure that sum of widths is equal to 12.',
                    'type' => 'column-width',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => '', 'type' => 'text'],

                'has-dynamic-values' => [
                    'value' => 'off',
                    'label' => 'Has dynamic value',
                    'tooltip' => 'Has dynamic value tooltip',
                    'type' => 'checkbox',
                ],
                'dynamic-value' => [
                    'value' => '',
                    'label' => 'Dynamic value name',
                    'tooltip' => 'Dynamic value name',
                    'type' => 'text',
                    'visible' => ['has-dynamic-values' => ['on']]
                ],
                'dynamic-value-index' => [
                    'value' => '',
                    'label' => 'Dynamic value index',
                    'tooltip' => 'Dynamic value index',
                    'type' => 'text',
                    'visible' => ['has-dynamic-values' => ['on']]
                ],

                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this element', 'hide' => 'Hide this element'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
            ],
            'email' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Email Address',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Email', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'placeholder' => ['value' => '', 'label' => 'Placeholder', 'tooltip' => 'The placeholder text will appear inside the field until the user starts to type.', 'type' => 'text'],
                'autocomplete' => [
                    'value' => 'email',
                    'label' => 'Autocomplete attribute',
                    'tooltip' => 'Choose the value of the autocomplete attribute. It helps browser to fill the field value, if required.',
                    'type' => 'select',
                    'options' => $this->getTranslatedAutocompleteOptions(),
                ],
                'description' => ['value' => 'Enter email address.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => ['value' => 'off', 'label' => 'Required', 'tooltip' => 'If enabled, the user must fill out the field.', 'type' => 'checkbox'],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'input-style' => [
                    'value' => ['size' => '', 'align' => ''],
                    'caption' => ['size' => 'Size', 'align' => 'Alignment'],
                    'label' => 'Input style',
                    'tooltip' => 'Adjust the input field style (size and text alignment).',
                    'type' => 'input-style',
                ],
                'icon' => [
                    'value' => [
                        'left-icon' => $this->options['fa-enable'] == 'on' ? ($this->options['fa-regular-enable'] == 'on' ? 'far fa-envelope' : 'fas fa-envelope') : 'leform-fa leform-fa-envelope-o',
                        'left-size' => '',
                        'left-color' => '',
                        'right-icon' => '',
                        'right-size' => '',
                        'right-color' => '',
                    ],
                    'caption' => ['left' => 'Left side', 'right' => 'Right side'],
                    'label' => 'Input icons',
                    'tooltip' => 'These icons appear inside/near of the input field.',
                    'type' => 'input-icons',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the input field.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'input' => [
                            'label' => 'Input field',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input',
                        ],
                        'input-hover' => [
                            'label' => 'Input field (hover)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                        ],
                        'input-focus' => [
                            'label' => 'Input field (focus)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:focus',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:focus',
                        ],
                        'input-icon-left' => [
                            'label' => 'Input field icon (left)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                        ],
                        'input-icon-right' => [
                            'label' => 'Input field icon (right)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'default' => [
                    'value' => '',
                    'label' => 'Default value',
                    'tooltip' => 'The default value is the value that the field has before the user has entered anything.',
                    'type' => 'field-or-text',
                    'isTextarea' => false
                ],
                'bind-field' => [
                    'value' => null,
                    'type' => 'bind-field'
                ],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'readonly' => ['value' => 'off', 'label' => 'Read only', 'tooltip' => 'If enabled, the user can not edit the field value.', 'type' => 'checkbox'],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'filters' => [
                    'values' => [["type" => "trim", "properties" => null]],
                    'allowed-values' => ['alpha', 'alphanumeric', 'digits', 'regex', 'strip-tags', 'trim'],
                    'label' => 'Filters',
                    'tooltip' => 'Filters allow you to strip various characters from the submitted value.',
                    'type' => 'filters',
                ],
                'validators' => [
                    'values' => [["type" => "email", "properties" => ['error' => '']]],
                    'allowed-values' => ['email', 'equal', 'equal-field', 'in-array', 'prevent-duplicates'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
                'csv-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['display-on-csv' => ['on']],
                ],
            ],
            'text' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Text',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Text', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'placeholder' => ['value' => '', 'label' => 'Placeholder', 'tooltip' => 'The placeholder text will appear inside the field until the user starts to type.', 'type' => 'text'],
                'autocomplete' => [
                    'value' => 'off',
                    'label' => 'Autocomplete attribute',
                    'tooltip' => 'Choose the value of the autocomplete attribute. It helps browser to fill the field value, if required.',
                    'type' => 'select',
                    'options' => $this->getTranslatedAutocompleteOptions(),
                ],
                'description' => ['value' => '', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => ['value' => 'off', 'label' => 'Required', 'tooltip' => 'If enabled, the user must fill out the field.', 'type' => 'checkbox'],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'input-style' => [
                    'value' => ['size' => '', 'align' => ''],
                    'caption' => ['size' => 'Size', 'align' => 'Alignment'],
                    'label' => 'Input style',
                    'tooltip' => 'Adjust the input field style (size and text alignment).',
                    'type' => 'input-style',
                ],
                'icon' => [
                    'value' => ['left-icon' => '', 'left-size' => '', 'left-color' => '', 'right-icon' => '', 'right-size' => '', 'right-color' => ''],
                    'caption' => ['left' => 'Left side', 'right' => 'Right side'],
                    'label' => 'Input icons',
                    'tooltip' => 'These icons appear inside/near of the input field.',
                    'type' => 'input-icons',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the input field.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'input' => [
                            'label' => 'Input field',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input',
                        ],
                        'input-hover' => [
                            'label' => 'Input field (hover)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                        ],
                        'input-focus' => [
                            'label' => 'Input field (focus)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:focus',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:focus',
                        ],
                        'input-icon-left' => [
                            'label' => 'Input field icon (left)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                        ],
                        'input-icon-right' => [
                            'label' => 'Input field icon (right)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'mask' => [
                    'value' => ['preset' => '', 'mask' => ''],
                    'label' => 'Mask',
                    'tooltip' =>
                    'Adjust the mask of the input field. Use the following special symbols:' .
                        '<br /><br />' .
                        '0 - mandatory digit' .
                        '<br />' .
                        '9 - optional digit' .
                        '<br />' .
                        'A - alphanumeric character' .
                        '<br />' .
                        'S - alpha character',
                    'preset-options' => [
                        // '(000)000-0000' => 'Phone number with area code: (000)000-0000',
                        // '(00)0000-0000' => 'Phone number with area code: (00)0000-0000',
                        '+0(000)000-0000' => 'International phone number: +0(000)000-0000',
                        '+00(000)000-0000' => 'International phone number: +00(000)000-0000',
                        // '099.099.099.099' => 'IP Address: 099.099.099.099',
                        // '000-00-0000' => 'SSN: 000-00-0000',
                        // '0000 0000 0000 0000' => 'Visa/Mastercard: 0000 0000 0000 0000',
                        // '0000 000000 00000' => 'AmEx: 0000 000000 00000',
                        'custom' => 'Custom Mask',
                    ],
                    'type' => 'mask',
                ],
                'default' => [
                    'value' => '',
                    'label' => 'Default value',
                    'tooltip' => 'The default value is the value that the field has before the user has entered anything.',
                    'type' => 'field-or-text',
                    'isTextarea' => false
                ],
                'bind-field' => [
                    'value' => null,
                    'type' => 'bind-field'
                ],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'readonly' => ['value' => 'off', 'label' => 'Read only', 'tooltip' => 'If enabled, the user can not edit the field value.', 'type' => 'checkbox'],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'external-datasource' => [
                    'value' => 'off',
                    'label' => 'Pre-fill with content from external source',
                    'type' => 'checkbox',
                ],
                'external-datasource-url' => [
                    'value' => '',
                    'label' => 'Source for the data (URI)',
                    'type' => 'text',
                    'visible' => ['external-datasource' => ['on']],
                ],
                'external-datasource-path' => [
                    'value' => '',
                    'label' => 'Path to the object with the options',
                    'tooltip' => 'Specify path according to dot notation.',
                    'type' => 'text',
                    'visible' => ['external-datasource' => ['on']],
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'filters' => [
                    'values' => [["type" => "trim", "properties" => null]],
                    'allowed-values' => ['alpha', 'alphanumeric', 'digits', 'regex', 'strip-tags', 'trim'],
                    'label' => 'Filters',
                    'tooltip' => 'Filters allow you to strip various characters from the submitted value.',
                    'type' => 'filters',
                ],
                'validators' => [
                    'values' => [],
                    'allowed-values' => ['alpha', 'alphanumeric', 'digits', 'email', 'equal', 'equal-field', 'greater', 'in-array', 'length', 'less', 'prevent-duplicates', 'regex', 'url'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
                'csv-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['display-on-csv' => ['on']],
                ],
            ],
            'textarea' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Textarea',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Text', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'placeholder' => ['value' => '', 'label' => 'Placeholder', 'tooltip' => 'The placeholder text will appear inside the field until the user starts to type.', 'type' => 'text'],
                'description' => ['value' => 'Type message.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => [
                    'value' => 'off',
                    'label' => 'Required',
                    'tooltip' => 'If enabled, the user must fill out the field.',
                    'caption' => 'The field is required',
                    'type' => 'checkbox',
                ],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'textarea-style' => [
                    'value' => ['height' => '', 'align' => ''],
                    'caption' => ['height' => 'Height', 'align' => 'Alignment'],
                    'label' => 'Textarea style',
                    'tooltip' => 'Adjust the textarea field style (size and text alignment).',
                    'type' => 'textarea-style',
                ],
                'icon' => [
                    'value' => ['left-icon' => '', 'left-size' => '', 'left-color' => '', 'right-icon' => '', 'right-size' => '', 'right-color' => ''],
                    'caption' => ['left' => 'Left side', 'right' => 'Right side'],
                    'label' => 'Textarea icons',
                    'tooltip' => 'These icons appear inside/near of the textarea field.',
                    'type' => 'input-icons',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Select the horizontal alignment of the description.',
                    'type' => 'description-style',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the textarea field.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'textarea' => [
                            'label' => 'Textarea',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input textarea',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input textarea',
                        ],
                        'textarea-hover' => [
                            'label' => 'Textarea (hover)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input textarea:hover',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input textarea:hover',
                        ],
                        'textarea-focus' => [
                            'label' => 'Textarea (focus)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input textarea:focus',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input textarea:focus',
                        ],
                        'textarea-icon-left' => [
                            'label' => 'Textarea icon (left)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                        ],
                        'textarea-icon-right' => [
                            'label' => 'Textarea icon (right)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'default' => [
                    'value' => '',
                    'label' => 'Default value',
                    'tooltip' => 'The default value is the value that the field has before the user has entered anything.',
                    'type' => 'field-or-text',
                    'isTextarea' => true
                ],
                'bind-field' => [
                    'value' => null,
                    'type' => 'bind-field'
                ],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'maxlength' => [
                    'value' => '',
                    'label' => 'Max length',
                    'tooltip' => 'Specifies the maximum number of characters allowed in the text area. Leave empty or set "0" for unlimited number of characters.',
                    'unit' => 'chars',
                    'type' => 'units',
                ],
                'readonly' => ['value' => 'off', 'label' => 'Read only', 'tooltip' => 'If enabled, the user can not edit the field value.', 'type' => 'checkbox'],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'external-datasource' => [
                    'value' => 'off',
                    'label' => 'Pre-fill with content from external source',
                    'type' => 'checkbox',
                ],
                'external-datasource-url' => [
                    'value' => '',
                    'label' => 'Source for the data (URI)',
                    'type' => 'text',
                    'visible' => ['external-datasource' => ['on']],
                ],
                'external-datasource-path' => [
                    'value' => '',
                    'label' => 'Path to the object with the options',
                    'tooltip' => 'Specify path according to dot notation.',
                    'type' => 'text',
                    'visible' => ['external-datasource' => ['on']],
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'filters' => [
                    'values' => [["type" => "trim", "properties" => null]],
                    'allowed-values' => ['alpha', 'alphanumeric', 'digits', 'regex', 'strip-tags', 'trim'],
                    'label' => 'Filters',
                    'tooltip' => 'Filters allow you to strip various characters from the submitted value.',
                    'type' => 'filters',
                ],
                'validators' => [
                    'values' => [],
                    'allowed-values' => ['alpha', 'alphanumeric', 'digits', 'email', 'equal', 'equal-field', 'greater', 'in-array', 'length', 'less', 'prevent-duplicates', 'regex', 'url'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
                'csv-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['display-on-csv' => ['on']],
                ],
            ],
            'select' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'DropDown',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Bitte auswählen', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                /*
                'submit-on-select' => [
                    'value' => 'off',
                    'label' => 'Submit on select',
                    'tooltip' => 'If enabled, the form is submitted when user do selection.',
                    'caption' => 'Submit on select',
                    'type' => 'checkbox',
                ],
                 */
                'options' => [
                    'multi-select' => 'off',
                    'values' => [['value' => 'Option 1', 'label' => 'Option 1']],
                    'label' => 'Options',
                    'tooltip' => 'These are the choices that the user will be able to choose from.',
                    'type' => 'options',
                ],
                'please-select-option' => [
                    'value' => 'off',
                    'label' => "'Please select' option",
                    'tooltip' => 'Adds an option to the top of the list to let the user choose no value.',
                    'type' => 'checkbox',
                ],
                'please-select-text' => ['value' => 'Please select', 'label' => "'Please select' text", 'type' => 'text', 'visible' => ['please-select-option' => ['on']]],
                'autocomplete' => [
                    'value' => 'off',
                    'label' => 'Autocomplete attribute',
                    'tooltip' => 'Choose the value of the autocomplete attribute. It helps browser to fill the field value, if required.',
                    'type' => 'select',
                    'options' => $this->getTranslatedAutocompleteOptions(),
                ],
                'description' => ['value' => 'Wert auswählen', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => [
                    'value' => 'off',
                    'label' => 'Required',
                    'tooltip' => 'If enabled, the user must fill out the field.',
                    'caption' => 'The field is required',
                    'type' => 'checkbox',
                ],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'input-style' => [
                    'value' => ['size' => '', 'align' => ''],
                    'caption' => ['size' => 'Size', 'align' => 'Alignment'],
                    'label' => 'Input style',
                    'tooltip' => 'Adjust the input field style (size and text alignment).',
                    'type' => 'input-style',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the input field.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'select' => [
                            'label' => 'Select box',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input select',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input select',
                        ],
                        'select-hover' => [
                            'label' => 'Select box (hover)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input select:hover',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input select:hover',
                        ],
                        'select-focus' => [
                            'label' => 'Select box (focus)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input select:focus',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input select:focus',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'external-datasource' => [
                    'value' => 'off',
                    'label' => 'Pre-fill with content from external source',
                    'type' => 'checkbox',
                ],
                'external-datasource-url' => [
                    'value' => '',
                    'label' => 'Source for the data (URI)',
                    'type' => 'text',
                    'visible' => ['external-datasource' => ['on']],
                ],
                'external-datasource-path' => [
                    'value' => '',
                    'label' => 'Path to the object with the options',
                    'tooltip' => 'Specify path according to dot notation.',
                    'type' => 'text',
                    'visible' => ['external-datasource' => ['on']],
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'validators' => [
                    'values' => [],
                    'allowed-values' => ['equal', 'equal-field', 'greater', 'in-array', 'less', 'prevent-duplicates', 'regex'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
                'csv-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['display-on-csv' => ['on']],
                ],
            ],
            'checkbox' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Checkbox',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Options', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'options' => [
                    'multi-select' => 'on',
                    'values' => [['value' => 'Option 1', 'label' => 'Option 1'], ['value' => 'Option 2', 'label' => 'Option 2'], ['value' => 'Option 3', 'label' => 'Option 3']],
                    'label' => 'Options',
                    'tooltip' => 'These are the choices that the user will be able to choose from.',
                    'type' => 'options',
                ],
                'description' => ['value' => 'Select options.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => [
                    'value' => 'off',
                    'label' => 'Required',
                    'tooltip' => 'If enabled, the user must fill out the field.',
                    'caption' => 'The field is required',
                    'type' => 'checkbox',
                ],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => '', 'type' => 'text'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'checkbox-style' => [
                    'value' => ['position' => '', 'align' => '', 'layout' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Alignment', 'layout' => 'Layout'],
                    'label' => 'Checkbox style',
                    'tooltip' => 'Choose how to display checkbox fields and their captions.',
                    'type' => 'local-checkbox-style',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'validators' => [
                    'values' => [],
                    'allowed-values' => ['in-array', 'prevent-duplicates'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
                'csv-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['display-on-csv' => ['on']],
                ],
            ],
            'imageselect' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Image select',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Options', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'mode' => ['value' => 'radio', 'label' => 'Mode', 'tooltip' => 'Select the mode of the Image Select.', 'type' => 'imageselect-mode'],
                /*
                'submit-on-select' => [
                    'value' => 'off',
                    'label' => 'Submit on select',
                    'tooltip' => 'If enabled, the form is submitted when user do selection.',
                    'caption' => 'Submit on select',
                    'type' => 'checkbox',
                    'visible' => ['mode' => ['radio']],
                ],
                 */
                'options' => [
                    'multi-select' => 'off',
                    'values' => [
                        ['value' => 'Option 1', 'label' => 'Option 1', 'image' => '/images/placeholder-image.png'],
                        ['value' => 'Option 2', 'label' => 'Option 2', 'image' => '/images/placeholder-image.png'],
                        ['value' => 'Option 3', 'label' => 'Option 3', 'image' => '/images/placeholder-image.png'],
                    ],
                    'label' => 'Options',
                    'tooltip' => 'These are the choices that the user will be able to choose from.',
                    'type' => 'image-options',
                ],
                'description' => ['value' => 'Select options.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => [
                    'value' => 'off',
                    'label' => 'Required',
                    'tooltip' => 'If enabled, the user must fill out the field.',
                    'caption' => 'The field is required',
                    'type' => 'checkbox',
                ],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'image-style' => [
                    'value' => ['width' => "120", 'height' => "160", 'size' => 'contain'],
                    'caption' => ['width' => 'Width', 'height' => 'Height', 'size' => 'Size'],
                    'label' => 'Image style',
                    'tooltip' => 'Choose how to display images.',
                    'type' => 'local-imageselect-style',
                ],
                'label-enable' => [
                    'value' => 'off',
                    'label' => 'Enable label',
                    'tooltip' => 'If enabled, the label will be displayed below the image.',
                    'caption' => 'Label enabled',
                    'type' => 'checkbox',
                ],
                'label-height' => ['value' => '60', 'label' => 'Label height', 'tooltip' => 'Set the height of label area.', 'unit' => 'px', 'type' => 'units', 'visible' => ['label-enable' => ['on']]],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'validators' => [
                    'values' => [],
                    'allowed-values' => ['in-array', 'prevent-duplicates'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
            ],
            'tile' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Tile',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Options', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'mode' => ['value' => 'radio', 'label' => 'Mode', 'tooltip' => 'Select the mode of the Tiles.', 'type' => 'tile-mode'],
                /*
                'submit-on-select' => [
                    'value' => 'off',
                    'label' => 'Submit on select',
                    'tooltip' => 'If enabled, the form is submitted when user do selection.',
                    'caption' => 'Submit on select',
                    'type' => 'checkbox',
                    'visible' => ['mode' => ['radio']],
                ],
                 */
                'options' => [
                    'multi-select' => 'off',
                    'values' => [['value' => 'Option 1', 'label' => 'Option 1'], ['value' => 'Option 2', 'label' => 'Option 2'], ['value' => 'Option 3', 'label' => 'Option 3']],
                    'label' => 'Options',
                    'tooltip' => 'These are the choices that the user will be able to choose from.',
                    'type' => 'options',
                ],
                'description' => ['value' => 'Select options.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => [
                    'value' => 'off',
                    'label' => 'Required',
                    'tooltip' => 'If enabled, the user must select at least one option.',
                    'caption' => 'The field is required',
                    'type' => 'checkbox',
                ],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'tile-style' => [
                    'value' => ['size' => '', 'width' => '', 'position' => '', 'layout' => ''],
                    'caption' => ['size' => 'Size', 'width' => 'Width', 'position' => 'Position', 'layout' => 'Layout'],
                    'label' => 'Tile style',
                    'tooltip' => 'Adjust the tile style.',
                    'type' => 'local-tile-style',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'validators' => [
                    'values' => [],
                    'allowed-values' => ['in-array', 'prevent-duplicates'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
                'csv-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['display-on-csv' => ['on']],
                ],
            ],
            'multiselect' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Multiselect',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Options', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'options' => [
                    'multi-select' => 'on',
                    'values' => [['value' => 'Option 1', 'label' => 'Option 1'], ['value' => 'Option 2', 'label' => 'Option 2'], ['value' => 'Option 3', 'label' => 'Option 3']],
                    'label' => 'Options',
                    'tooltip' => 'These are the choices that the user will be able to choose from.',
                    'type' => 'options',
                ],
                'description' => ['value' => 'Select options.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => [
                    'value' => 'off',
                    'label' => 'Required',
                    'tooltip' => 'If enabled, the user must fill out the field.',
                    'caption' => 'The field is required',
                    'type' => 'checkbox',
                ],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'multiselect-style' => [
                    'value' => ['height' => '', 'align' => ''],
                    'caption' => ['height' => 'Height', 'align' => 'Alignment'],
                    'label' => 'Multiselect style',
                    'tooltip' => 'Adjust the multiselect field style (size and text alignment).',
                    'type' => 'local-multiselect-style',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'max-allowed' => ['value' => '0', 'label' => 'Maximum selected options', 'tooltip' => 'Enter how many options can be selected. Set 0 for unlimited number.', 'type' => 'integer'],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'validators' => [
                    'values' => [],
                    'allowed-values' => ['in-array', 'prevent-duplicates'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
            ],
            'radio' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Radio button',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Options', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                /*
                'submit-on-select' => [
                    'value' => 'off',
                    'label' => 'Submit on select',
                    'tooltip' => 'If enabled, the form is submitted when user do selection.',
                    'caption' => 'Submit on select',
                    'type' => 'checkbox',
                ],
                 */
                'options' => [
                    'multi-select' => 'off',
                    'values' => [['value' => 'Option 1', 'label' => 'Option 1'], ['value' => 'Option 2', 'label' => 'Option 2'], ['value' => 'Option 3', 'label' => 'Option 3']],
                    'label' => 'Options',
                    'tooltip' => 'These are the choices that the user will be able to choose from.',
                    'type' => 'options',
                ],
                'description' => ['value' => 'Select option.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => [
                    'value' => 'off',
                    'label' => 'Required',
                    'tooltip' => 'If enabled, the user must fill out the field.',
                    'caption' => 'The field is required',
                    'type' => 'checkbox',
                ],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'radio-style' => [
                    'value' => ['position' => '', 'align' => '', 'layout' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Alignment', 'layout' => 'Layout'],
                    'label' => 'Radio button style',
                    'tooltip' => 'Choose how to display checkbox fields and their captions.',
                    'type' => 'local-checkbox-style',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'validators' => [
                    'values' => [],
                    'allowed-values' => ['in-array', 'prevent-duplicates'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
                'csv-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['display-on-csv' => ['on']],
                ],
            ],
            'matrix' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Matrix',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => [
                    'value' => 'Matrix',
                    'label' => 'Label',
                    'tooltip' => 'This is the label of the field.',
                    'type' => 'text'
                ],
                'description' => [
                    'value' => 'Select options.',
                    'label' => 'Description',
                    'tooltip' => 'This description appears below the field.',
                    'type' => 'text'
                ],
                'left' => [
                    'multi-select' => 'on',
                    'values' => [
                        ['value' => 'One', 'label' => 'One'],
                        ['value' => 'Two', 'label' => 'Two'],
                        ['value' => 'Three', 'label' => 'Three']
                    ],
                    'label' => 'Y-axis',
                    'tooltip' => 'These are the choices that the user will be able to choose from.',
                    'type' => 'options',
                ],
                'top' => [
                    'multi-select' => 'on',
                    'values' => [
                        ['value' => 'One', 'label' => 'One'],
                        ['value' => 'Two', 'label' => 'Two'],
                        ['value' => 'Three', 'label' => 'Three']
                    ],
                    'label' => 'X-axis',
                    'tooltip' => 'These are the choices that the user will be able to choose from.',
                    'type' => 'options',
                ],
                'multi-select' => [
                    'value' => 'on',
                    'label' => 'Is multi select',
                    'tooltip' => 'If enabled, the user must fill out the field.',
                    'caption' => 'The field is required',
                    'type' => 'checkbox',
                ],
                'required' => [
                    'value' => 'off',
                    'label' => 'Required',
                    'tooltip' => 'If enabled, the user must fill out the field.',
                    'caption' => 'The field is required',
                    'type' => 'checkbox',
                ],
                'required-error' => [
                    'value' => 'This field is required.',
                    'label' => 'Error message',
                    'type' => 'error',
                    'visible' => ['required' => ['on']]
                ],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
            ],
            'repeater-input' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Repeater input',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => [
                    'value' => 'Repeater input',
                    'label' => 'Label',
                    'tooltip' => 'This is the label of the field.',
                    'type' => 'text'
                ],
                'description' => [
                    'value' => 'Repeater input.',
                    'label' => 'Description',
                    'tooltip' => 'This description appears below the field.',
                    'type' => 'text'
                ],
                'has-footer' => [
                    'value' => 'off',
                    'label' => 'Has footer',
                    'tooltip' => 'Enable this option if you want to use footer',
                    'type' => 'checkbox',
                ],
                'footer-tolals' => [
                    'value' => '',
                    'label' => 'Footer',
                    'tooltip' => 'Footer',
                    'type' => 'repeater-input-footer',
                    'visible' => ['has-footer' => ['on']],
                ],
                'fields' => [
                    'values' => [],
                    'label' => 'Fields',
                    'tooltip' => 'This description appears below the field.',
                    'type' => 'repeater-input-fields'
                ],
                'add-row-width' => [
                    'value' => 1,
                    'label' => 'Add row button width',
                    'tooltip' => 'Set width.',
                    'type' => 'text-number-natural-num',
                ],
                'add-row-label' => [
                    'value' => 'Add row',
                    'label' => 'Add row button label',
                    'tooltip' => 'This is the label of the add row button.',
                    'type' => 'text'
                ],
                'has-borders' => [
                    'value' => 'off',
                    'label' => 'Has borders',
                    'tooltip' => 'Enable this option if you want borders.',
                    'type' => 'checkbox',
                ],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
                'csv-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['display-on-csv' => ['on']],
                ],
                /*
                    'row-count' => [
                        'value' => 4,
                        'label' => 'Row count',
                        'tooltip' => 'Set row count.',
                        'type' => 'text-number',
                    ],
                 */
                /*
                    'required' => [
                        'value' => 'off',
                        'label' => 'Required',
                        'tooltip' => 'If enabled, the user must fill out the field.',
                        'caption' => 'The field is required',
                        'type' => 'checkbox',
                    ],
                    'required-error' => [
                        'value' => 'This field is required.',
                        'label' => 'Error message',
                        'type' => 'error',
                        'visible' => ['required' => ['on']]
                    ],
                 */
            ],
            'iban-input' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'IBAN & BIC input',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => [
                    'value' => 'IBAN & BIC input',
                    'label' => 'Label',
                    'tooltip' => 'This is the label of the field.',
                    'type' => 'text'
                ],
                'description' => [
                    'value' => 'IBAN & BIC input',
                    'label' => 'Description',
                    'tooltip' => 'This description appears below the field.',
                    'type' => 'text'
                ],
                'required' => ['value' => 'on', 'label' => 'Required', 'tooltip' => 'If enabled, the user must fill out the field.', 'type' => 'checkbox'],
                'required-error' => ['value' => 'IBAN invalid, please correct.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'default' => [
                    'value' => '',
                    'label' => 'Default Iban value',
                    'tooltip' => 'The default value is the value that the field has before the user has entered anything.',
                    'type' => 'text',
                    'isTextarea' => false
                ],
                'defaultbic' => [
                    'value' => '',
                    'label' => 'Default Bic value',
                    'tooltip' => 'The default value is the value that the field has before the user has entered anything.',
                    'type' => 'text',
                    'isTextarea' => false
                ],
            ],
            'date' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Date',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Date', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'placeholder' => ['value' => '', 'label' => 'Placeholder', 'tooltip' => 'The placeholder text will appear inside the field until the user starts to type.', 'type' => 'text'],
                'autocomplete' => [
                    'value' => 'off',
                    'label' => 'Autocomplete attribute',
                    'tooltip' => 'Choose the value of the autocomplete attribute. It helps browser to fill the field value, if required.',
                    'type' => 'select',
                    'options' => $this->getTranslatedAutocompleteOptions(),
                ],
                'description' => ['value' => 'Datum auswählen.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => ['value' => 'off', 'label' => 'Required', 'tooltip' => 'If enabled, the user must fill out the field.', 'type' => 'checkbox'],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'input-style' => [
                    'value' => ['size' => '', 'align' => ''],
                    'caption' => ['size' => 'Size', 'align' => 'Alignment'],
                    'label' => 'Input style',
                    'tooltip' => 'Adjust the input field style (size and text alignment).',
                    'type' => 'input-style',
                ],
                'icon' => [
                    'value' => [
                        'left-icon' => '',
                        'left-size' => '',
                        'left-color' => '',
                        'right-icon' => $this->options['fa-enable'] == 'on' ? ($this->options['fa-regular-enable'] == 'on' ? 'far fa-calendar-alt' : 'fas fa-calendar-alt') : 'leform-fa leform-fa-calendar',
                        'right-size' => '',
                        'right-color' => '',
                    ],
                    'caption' => ['left' => 'Left side', 'right' => 'Right side'],
                    'label' => 'Input icons',
                    'tooltip' => 'These icons appear inside/near of the input field.',
                    'type' => 'input-icons',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the input field.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'input' => [
                            'label' => 'Input field',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input',
                        ],
                        'input-hover' => [
                            'label' => 'Input field (hover)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                        ],
                        'input-focus' => [
                            'label' => 'Input field (focus)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:focus',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:focus',
                        ],
                        'input-icon-left' => [
                            'label' => 'Input field icon (left)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                        ],
                        'input-icon-right' => [
                            'label' => 'Input field icon (right)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'default' => [
                    'value' => ['type' => 'date', 'date' => '', 'offset' => '0'],
                    'caption' => ['type' => 'Type', 'date' => 'Date', 'offset' => 'Offset'],
                    'type-values' => [
                        'none' => 'None',
                        'yesterday' => 'Yesterday',
                        'today' => 'Today',
                        'offset' => 'Today + N days',
                        'tomorrow' => 'Tomorrow',
                        'date' => 'Fixed date',
                    ],
                    'label' => 'Default',
                    'tooltip' => 'The default value is the value that the field has before the user has entered anything.',
                    'type' => 'date-default',
                ],
                //                    'default' => array('value' => '', 'label' => 'Default value', 'tooltip' => 'The default value is the value that the field has before the user has entered anything.', 'type' => 'date'),
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'min-date' => [
                    'value' => ['type' => '', 'date' => '', 'field' => '', 'offset' => '0'],
                    'caption' => ['type' => 'Type', 'date' => 'Date', 'field' => 'Field', 'offset' => 'Offset'],
                    'type-values' => [
                        'none' => 'None',
                        'yesterday' => 'Yesterday',
                        'today' => 'Today',
                        'offset' => 'Today + N days',
                        'tomorrow' => 'Tomorrow',
                        'date' => 'Fixed date',
                        'field' => 'Other field',
                    ],
                    'label' => 'Minimum date',
                    'tooltip' => 'Adjust the minimum date that can be selected.',
                    'type' => 'date-limit',
                ],
                'min-date-error' => [
                    'value' => 'The value is out of range.',
                    'label' => 'Error message',
                    'tooltip' => 'This error message appears if submitted date is less than minimum date.',
                    'type' => 'error',
                    'visible' => ['min-date-type' => ['yesterday', 'today', 'tomorrow', 'date', 'field', 'offset']],
                ],
                'max-date' => [
                    'value' => ['type' => '', 'date' => '', 'field' => '', 'offset' => '0'],
                    'caption' => ['type' => 'Type', 'date' => 'Date', 'field' => 'Field', 'offset' => 'Offset'],
                    'type-values' => [
                        'none' => 'None',
                        'yesterday' => 'Yesterday',
                        'today' => 'Today',
                        'offset' => 'Today + N days',
                        'tomorrow' => 'Tomorrow',
                        'date' => 'Fixed date',
                        'field' => 'Other field',
                    ],
                    'label' => 'Maximum date',
                    'tooltip' => 'Adjust the maximum date that can be selected.',
                    'type' => 'date-limit',
                ],
                'max-date-error' => [
                    'value' => 'The value is out of range.',
                    'label' => 'Error message',
                    'tooltip' => 'This error message appears if submitted date is more than minimum date.',
                    'type' => 'error',
                    'visible' => ['max-date-type' => ['yesterday', 'today', 'tomorrow', 'date', 'field', 'offset']],
                ],
                'readonly' => ['value' => 'off', 'label' => 'Read only', 'tooltip' => 'If enabled, the user can not edit the field value.', 'type' => 'checkbox'],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'filters' => [
                    'values' => [["type" => "trim", "properties" => null]],
                    'allowed-values' => ['alpha', 'alphanumeric', 'digits', 'regex', 'strip-tags', 'trim'],
                    'label' => 'Filters',
                    'tooltip' => 'Filters allow you to strip various characters from the submitted value.',
                    'type' => 'filters',
                ],
                'validators' => [
                    'values' => [["type" => "date", "properties" => ['error' => '']]],
                    'allowed-values' => ['date'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-date-format' => [
                    'value' => '',
                    'label' => 'Formatierung des Datums (bspw. “d.m.Y”)',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
                'csv-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['display-on-csv' => ['on']],
                ],
            ],
            'time' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Time',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Time', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'placeholder' => ['value' => '', 'label' => 'Placeholder', 'tooltip' => 'The placeholder text will appear inside the field until the user starts to type.', 'type' => 'text'],
                'description' => ['value' => 'Select time.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => ['value' => 'off', 'label' => 'Required', 'tooltip' => 'If enabled, the user must fill out the field.', 'type' => 'checkbox'],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'input-style' => [
                    'value' => ['size' => '', 'align' => ''],
                    'caption' => ['size' => 'Size', 'align' => 'Alignment'],
                    'label' => 'Input style',
                    'tooltip' => 'Adjust the input field style (size and text alignment).',
                    'type' => 'input-style',
                ],
                'icon' => [
                    'value' => [
                        'left-icon' => '',
                        'left-size' => '',
                        'left-color' => '',
                        'right-icon' => $this->options['fa-enable'] == 'on' ? ($this->options['fa-regular-enable'] == 'on' ? 'far fa-clock' : 'fas fa-clock') : 'leform-fa leform-fa-clock-o',
                        'right-size' => '',
                        'right-color' => '',
                    ],
                    'caption' => ['left' => 'Left side', 'right' => 'Right side'],
                    'label' => 'Input icons',
                    'tooltip' => 'These icons appear inside/near of the input field.',
                    'type' => 'input-icons',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the input field.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'input' => [
                            'label' => 'Input field',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input',
                        ],
                        'input-hover' => [
                            'label' => 'Input field (hover)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                        ],
                        'input-focus' => [
                            'label' => 'Input field (focus)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:focus',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:focus',
                        ],
                        'input-icon-left' => [
                            'label' => 'Input field icon (left)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                        ],
                        'input-icon-right' => [
                            'label' => 'Input field icon (right)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'default' => ['value' => '', 'label' => 'Default value', 'tooltip' => 'The default value is the value that the field has before the user has entered anything.', 'type' => 'time'],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'min-time' => [
                    'value' => ['type' => '', 'time' => '', 'field' => ''],
                    'caption' => ['type' => 'Type', 'time' => 'Time', 'field' => 'Field'],
                    'type-values' => ['none' => 'None', 'time' => 'Fixed time', 'field' => 'Other field'],
                    'label' => 'Minimum time',
                    'tooltip' => 'Adjust the minimum time that can be selected.',
                    'type' => 'time-limit',
                ],
                'min-time-error' => [
                    'value' => 'The value is out of range.',
                    'label' => 'Error message',
                    'tooltip' => 'This error message appears if submitted time is less than minimum time.',
                    'type' => 'error',
                    'visible' => ['min-time-type' => ['time', 'field']],
                ],
                'max-time' => [
                    'value' => ['type' => '', 'time' => '', 'field' => ''],
                    'caption' => ['type' => 'Type', 'time' => 'Time', 'field' => 'Field'],
                    'type-values' => ['none' => 'None', 'time' => 'Fixed time', 'field' => 'Other field'],
                    'label' => 'Maximum time',
                    'tooltip' => 'Adjust the maximum time that can be selected.',
                    'type' => 'time-limit',
                ],
                'max-time-error' => [
                    'value' => 'The value is out of range.',
                    'label' => 'Error message',
                    'tooltip' => 'This error message appears if submitted time is more than minimum time.',
                    'type' => 'error',
                    'visible' => ['max-time-type' => ['time', 'field']],
                ],
                'interval' => ['value' => '10', 'label' => 'Minute interval', 'tooltip' => 'Enter the minute interval.', 'type' => 'integer'],
                'readonly' => ['value' => 'off', 'label' => 'Read only', 'tooltip' => 'If enabled, the user can not edit the field value manually, only via timepicker.', 'type' => 'checkbox'],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'filters' => [
                    'values' => [["type" => "trim", "properties" => null]],
                    'allowed-values' => ['alpha', 'alphanumeric', 'digits', 'regex', 'strip-tags', 'trim'],
                    'label' => 'Filters',
                    'tooltip' => 'Filters allow you to strip various characters from the submitted value.',
                    'type' => 'filters',
                ],
                'validators' => [
                    'values' => [["type" => "time", "properties" => ['error' => '']]],
                    'allowed-values' => ['time'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
            ],
            'file' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Upload',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Upload', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'button-label' => ['value' => 'Browse...', 'label' => 'Caption', 'tooltip' => 'This is the caption of upload button.', 'type' => 'text'],
                'description' => ['value' => 'Upload file.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => ['value' => 'off', 'label' => 'Required', 'tooltip' => 'If enabled, the user must fill out the field.', 'type' => 'checkbox'],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'button-style' => [
                    'value' => ['size' => '', 'width' => '', 'position' => 'left'],
                    'caption' => ['size' => 'Size', 'width' => 'Width', 'position' => 'Position'],
                    'label' => 'Button style',
                    'tooltip' => 'Adjust the button size and position.',
                    'type' => 'local-button-style',
                ],
                'icon' => [
                    'value' => ['left' => '', 'right' => $this->options['fa-enable'] == 'on' ? 'fas fa-upload' : 'leform-fa leform-fa-upload'],
                    'caption' => ['left' => 'Left side', 'right' => 'Right side'],
                    'label' => 'Icons',
                    'tooltip' => 'These icons appear near the button caption.',
                    'type' => 'button-icons',
                ],
                'colors-sections' => [
                    'type' => 'sections',
                    'sections' => [
                        'button-default' => ['label' => 'Default', 'icon' => 'fas fa-globe'],
                        'button-hover' => ['label' => 'Hover', 'icon' => 'far fa-hand-pointer'],
                        'button-active' => ['label' => 'Active', 'icon' => 'far fa-paper-plane'],
                    ],
                ],
                'start-button-default' => ['type' => 'section-start', 'section' => 'button-default'],
                'colors' => [
                    'value' => ['background' => '', 'border' => '', 'text' => ''],
                    'caption' => ['background' => 'Background', 'border' => 'Border', 'text' => 'Text'],
                    'label' => 'Colors',
                    'tooltip' => 'Adjust the colors of the button.',
                    'type' => 'colors',
                ],
                'end-button-default' => ['type' => 'section-end'],
                'start-button-hover' => ['type' => 'section-start', 'section' => 'button-hover'],
                'colors-hover' => [
                    'value' => ['background' => '', 'border' => '', 'text' => ''],
                    'caption' => ['background' => 'Background', 'border' => 'Border', 'text' => 'Text'],
                    'label' => 'Colors',
                    'tooltip' => 'Adjust the colors of the hovered button.',
                    'type' => 'colors',
                ],
                'end-button-hover' => ['type' => 'section-end'],
                'start-button-active' => ['type' => 'section-start', 'section' => 'button-active'],
                'colors-active' => [
                    'value' => ['background' => '', 'border' => '', 'text' => ''],
                    'caption' => ['background' => 'Background', 'border' => 'Border', 'text' => 'Text'],
                    'label' => 'Colors',
                    'tooltip' => 'Adjust the colors of the active button.',
                    'type' => 'colors',
                ],
                'end-button-active' => ['type' => 'section-end'],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the button.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'button' => [
                            'label' => 'Button',
                            'admin-class' => '.leform-element-{element-id} a.leform-button',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button',
                        ],
                        'button-hover' => [
                            'label' => 'Button (hover)',
                            'admin-class' => '.leform-element-{element-id} a.leform-button:hover',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button:hover',
                        ],
                        'button-active' => [
                            'label' => 'Button (active)',
                            'admin-class' => '.leform-element-{element-id} a.leform-button:active',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button:active',
                        ],
                        'button-icon-left' => [
                            'label' => 'Button icon (left)',
                            'admin-class' => '.leform-element-{element-id} a.leform-button i.leform-icon-left',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button i.leform-icon-left',
                        ],
                        'button-icon-right' => [
                            'label' => 'Button icon (right)',
                            'admin-class' => '.leform-element-{element-id} a.leform-button i.leform-icon-right',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button i.leform-icon-right',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'allowed-extensions' => [
                    'value' => 'gif, jpg, jpeg, png',
                    'label' => 'Allowed extensions',
                    'tooltip' => 'Enter the comma-separated list of allowed file extensions.',
                    'type' => 'text',
                ],
                'allowed-extensions-error' => [
                    'value' => 'Selected file extension is not allowed.',
                    'label' => 'Error message',
                    'tooltip' => 'This message appears if user tries to upload any file with extension not from the list.',
                    'type' => 'error',
                ],
                'max-size' => [
                    'value' => '10',
                    'label' => 'Maximum allowed size',
                    'tooltip' => sprintf('Enter the maximum size of a file in MB. According to your PHP settings, the maximum file size allowed is %s. Do not exceed this value.', ini_get('upload_max_filesize')),
                    'unit' => 'mb',
                    'type' => 'units',
                ],
                'max-size-error' => [
                    'value' => 'Selected file is too big.',
                    'label' => 'Error message',
                    'tooltip' => 'This message appears if user tries to upload any file bigger then maximum allowed file size.',
                    'type' => 'error',
                ],
                'max-files' => ['value' => '3', 'label' => 'Maximum number of files', 'tooltip' => 'Enter the maximum number of files that can be uploaded by user.', 'type' => 'integer'],
                'max-files-error' => [
                    'value' => 'Too many files.',
                    'label' => 'Error message',
                    'tooltip' => 'This message appears if user tries to upload more files then maximum number of files.',
                    'type' => 'error',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
            ],
            'star-rating' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Rating',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Rating', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                /*
                'submit-on-select' => [
                    'value' => 'off',
                    'label' => 'Submit on select',
                    'tooltip' => 'If enabled, the form is submitted when user do selection.',
                    'caption' => 'Submit on select',
                    'type' => 'checkbox',
                ],
                 */
                'description' => ['value' => 'Rate us.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => ['value' => 'off', 'label' => 'Required', 'tooltip' => 'If enabled, the user must fill out the field.', 'type' => 'checkbox'],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'star-style' => [
                    'value' => ['position' => 'left', 'size' => 'medium', 'color-unrated' => '#aaa', 'color-rated' => '#FFD700'],
                    'caption' => ['position' => 'Position', 'size' => 'Size', 'color-unrated' => 'Unrated', 'color-rated' => 'Rated'],
                    'label' => 'Star style',
                    'tooltip' => 'Adjust the style of stars.',
                    'type' => 'star-style',
                ],
                'overwrite-global-theme-colour' => [
                    'value' => 'off',
                    'label' => 'Overwrite global theme colour',
                    'tooltip' => 'Overwrite global theme colour',
                    'type' => 'checkbox',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'total-stars' => [
                    'value' => '5',
                    'label' => 'Number of stars',
                    'tooltip' => 'Choose the total number of stars.',
                    'type' => 'select',
                    'options' => [
                        '3' => '3 Stars',
                        '4' => '4 Stars',
                        '5' => '5 Stars',
                        '6' => '6 Stars',
                        '7' => '7 Stars',
                        '8' => '8 Stars',
                        '9' => '9 Stars',
                        '10' => '10 Stars',
                    ],
                ],
                'default' => [
                    'value' => '0',
                    'label' => 'Default rating',
                    'tooltip' => 'The default value is the value that the field has before the user has entered anything.',
                    'type' => 'select',
                    'options' => [
                        '0' => 'No rating',
                        '1' => '1 Star',
                        '2' => '2 Stars',
                        '3' => '3 Stars',
                        '4' => '4 Stars',
                        '5' => '5 Stars',
                        '6' => '6 Stars',
                        '7' => '7 Stars',
                        '8' => '8 Stars',
                        '9' => '9 Stars',
                        '10' => '10 Stars',
                    ],
                ],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
            ],
            'password' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Password',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Password', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'placeholder' => ['value' => '', 'label' => 'Placeholder', 'tooltip' => 'The placeholder text will appear inside the field until the user starts to type.', 'type' => 'text'],
                'description' => ['value' => 'Enter your password.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => ['value' => 'off', 'label' => 'Required', 'tooltip' => 'If enabled, the user must fill out the field.', 'type' => 'checkbox'],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'input-style' => [
                    'value' => ['size' => '', 'align' => ''],
                    'caption' => ['size' => 'Size', 'align' => 'Alignment'],
                    'label' => 'Input style',
                    'tooltip' => 'Adjust the input field style (size and text alignment).',
                    'type' => 'input-style',
                ],
                'icon' => [
                    'value' => ['left-icon' => $this->options['fa-enable'] == 'on' ? 'fas fa-lock' : 'leform-fa leform-fa-lock', 'left-size' => '', 'left-color' => '', 'right-icon' => '', 'right-size' => '', 'right-color' => ''],
                    'caption' => ['left' => 'Left side', 'right' => 'Right side'],
                    'label' => 'Input icons',
                    'tooltip' => 'These icons appear inside/near of the input field.',
                    'type' => 'input-icons',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the input field.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'input' => [
                            'label' => 'Input field',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input',
                        ],
                        'input-hover' => [
                            'label' => 'Input field (hover)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                        ],
                        'input-focus' => [
                            'label' => 'Input field (focus)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:focus',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:focus',
                        ],
                        'input-icon-left' => [
                            'label' => 'Input field icon (left)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                        ],
                        'input-icon-right' => [
                            'label' => 'Input field icon (right)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'min-length' => ['value' => '7', 'label' => 'Minimum length', 'tooltip' => 'Enter the minimum password length.', 'type' => 'integer'],
                'min-length-error' => [
                    'value' => 'The password is too short.',
                    'label' => 'Error message',
                    'tooltip' => 'This message appears if submitted password is too short.',
                    'type' => 'error',
                ],
                'capital-mandatory' => [
                    'value' => 'off',
                    'label' => 'Capital letters is mandatory',
                    'tooltip' => 'If enabled, the password must contains at least one capital letter.',
                    'type' => 'checkbox',
                ],
                'capital-mandatory-error' => ['value' => 'The password must contain capital letter.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['capital-mandatory' => ['on']]],
                'digit-mandatory' => ['value' => 'off', 'label' => 'Digit is mandatory', 'tooltip' => 'If enabled, the password must contains at least one digit.', 'type' => 'checkbox'],
                'digit-mandatory-error' => ['value' => 'The password must contain digit.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['digit-mandatory' => ['on']]],
                'special-mandatory' => [
                    'value' => 'off',
                    'label' => 'Special character is mandatory',
                    'tooltip' => 'If enabled, the password must contains at least one special character: !$#%^&*~_-(){}[]\|/?.',
                    'type' => 'checkbox',
                ],
                'special-mandatory-error' => ['value' => 'The password must contain special character.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['special-mandatory' => ['on']]],
                'save' => [
                    'value' => 'off',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
            ],
            'signature' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Signature',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Unterschrift', 'label' => 'Label', 'tooltip' => 'This is the label of the signature pad.', 'type' => 'text'],
                'description' => ['value' => 'Unterschreiben', 'label' => 'Description', 'tooltip' => 'This description appears below the signature pad.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => ['value' => 'off', 'label' => 'Required', 'tooltip' => 'If enabled, the user must put signature.', 'type' => 'checkbox'],
                'required-error' => ['value' => 'Signature is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'height' => ['value' => '220', 'label' => 'Pad height', 'tooltip' => 'Set the height of signature pad.', 'unit' => 'px', 'type' => 'units'],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the input field.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
                'csv-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['display-on-csv' => ['on']],
                ],
            ],
            'rangeslider' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Range',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Value', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'description' => ['value' => 'Select the value.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'grid-enable' => ['value' => 'off', 'label' => 'Show grid', 'tooltip' => 'Enables grid of values.', 'type' => 'checkbox'],
                'min-max-labels' => ['value' => 'off', 'label' => 'Show min/max labels', 'tooltip' => 'Enables labels for min and max values.', 'type' => 'checkbox'],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the input field.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'range' => [
                    'value' => ['value1' => '0', 'value2' => '100', 'value3' => '1'],
                    'caption' => ['value1' => 'Min', 'value2' => 'Max', 'value3' => 'Step'],
                    'label' => 'Range size',
                    'tooltip' => 'Set basic parameters of range slider. Min - slider minimum value. Max - slider maximum value. Step - slider step (always > 0).',
                    'type' => 'three-numbers',
                ],
                'handle' => [
                    'value' => '30',
                    'label' => 'Handle value',
                    'tooltip' => 'The default value is the value that the field has before the user has entered anything. If range slider has 2 handles, this is the default value of the left handle.',
                    'type' => 'integer',
                ],
                'double' => ['value' => 'off', 'label' => 'Double handle', 'tooltip' => 'Enables second handle.', 'type' => 'checkbox'],
                'handle2' => ['value' => '70', 'label' => 'Second handle value', 'tooltip' => 'This is the default value of the right handle.', 'type' => 'integer', 'visible' => ['double' => ['on']]],
                'prefix' => ['value' => '', 'label' => 'Value prefix', 'tooltip' => 'Set prefix for values. Will be set up right before the number. For example - $100.', 'type' => 'text'],
                'postfix' => ['value' => '', 'label' => 'Value postfix', 'tooltip' => 'Set postfix for values. Will be set up right after the number. For example - 100k.', 'type' => 'text'],
                'readonly' => ['value' => 'off', 'label' => 'Read only', 'tooltip' => 'If enabled, the user can not edit the field value.', 'type' => 'checkbox'],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'validators' => [
                    'values' => [],
                    'allowed-values' => ['equal', 'greater', 'in-array', 'less'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid. If range slider has 2 handles, both of them must match validator criteria.',
                    'type' => 'validators',
                ],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
                'csv-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['display-on-csv' => ['on']],
                ],
            ],
            'number' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Number',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Value', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'placeholder' => ['value' => '', 'label' => 'Placeholder', 'tooltip' => 'The placeholder text will appear inside the field until the user starts to type.', 'type' => 'text'],
                'description' => ['value' => 'Enter your value.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'required' => ['value' => 'off', 'label' => 'Required', 'tooltip' => 'If enabled, the user must fill out the field.', 'type' => 'checkbox'],
                'required-error' => ['value' => 'This field is required.', 'label' => 'Error message', 'type' => 'error', 'visible' => ['required' => ['on']]],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'input-style' => [
                    'value' => ['size' => '', 'align' => 'left'],
                    'caption' => ['size' => 'Size', 'align' => 'Alignment'],
                    'label' => 'Input style',
                    'tooltip' => 'Adjust the input field style (size and text alignment).',
                    'type' => 'input-style',
                ],
                'icon' => [
                    'value' => ['left-icon' => '', 'left-size' => '', 'left-color' => '', 'right-icon' => '', 'right-size' => '', 'right-color' => ''],
                    'caption' => ['left' => 'Left side', 'right' => 'Right side'],
                    'label' => 'Input icons',
                    'tooltip' => 'These icons appear inside/near of the input field.',
                    'type' => 'input-icons',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the input field.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'input' => [
                            'label' => 'Input field',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input',
                        ],
                        'input-hover' => [
                            'label' => 'Input field (hover)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                        ],
                        'input-focus' => [
                            'label' => 'Input field (focus)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:focus',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:focus',
                        ],
                        'input-icon-left' => [
                            'label' => 'Input field icon (left)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-left',
                        ],
                        'input-icon-right' => [
                            'label' => 'Input field icon (right)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input i.leform-icon-right',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'number' => [
                    'value' => ['value1' => '', 'value2' => '', 'value3' => ''],
                    'caption' => ['value1' => 'Min', 'value2' => 'Max', 'value3' => 'Default'],
                    'label' => 'Value',
                    'tooltip' => 'Set basic parameters of number input. Min - minimum value. Max - maximum value. Default - the value that the field has before the user has entered anything.',
                    'type' => 'three-numbers',
                ],
                'decimal' => [
                    'value' => '0',
                    'label' => 'Decimal digits',
                    'tooltip' => 'Select the allowed number of digits after the decimal separator.',
                    'options' => ['0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '6' => '6', '8' => '8'],
                    'type' => 'select',
                ],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'readonly' => ['value' => 'off', 'label' => 'Read only', 'tooltip' => 'If enabled, the user can not edit the field value.', 'type' => 'checkbox'],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'validators' => [
                    'values' => [],
                    'allowed-values' => ['equal', 'equal-field', 'greater', 'in-array', 'less'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
            ],
            'numspinner' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Number',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Value', 'label' => 'Label', 'tooltip' => 'This is the label of the field.', 'type' => 'text'],
                'description' => ['value' => 'Enter your value.', 'label' => 'Description', 'tooltip' => 'This description appears below the field.', 'type' => 'text'],
                'tooltip' => [
                    'value' => '',
                    'label' => 'Tooltip',
                    'tooltip' => 'The tooltip appears when user click/hover tooltip anchor. The location of tooltip anchor is configured on Form Settings (tab "Style").',
                    'type' => 'text',
                ],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'label-style' => [
                    'value' => ['position' => '', 'width' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'width' => 'Width', 'align' => 'Alignment'],
                    'label' => 'Label style',
                    'tooltip' => 'Choose where to display the label relative to the field and its alignment.',
                    'type' => 'label-style',
                ],
                'input-style' => [
                    'value' => ['size' => '', 'align' => 'right'],
                    'caption' => ['size' => 'Size', 'align' => 'Alignment'],
                    'label' => 'Input style',
                    'tooltip' => 'Adjust the input field style (size and text alignment).',
                    'type' => 'input-style',
                ],
                'description-style' => [
                    'value' => ['position' => '', 'align' => ''],
                    'caption' => ['position' => 'Position', 'align' => 'Align'],
                    'label' => 'Description style',
                    'tooltip' => 'Choose where to display the description relative to the field and its alignment.',
                    'type' => 'description-style',
                ],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the input field.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'label' => [
                            'label' => 'Label',
                            'admin-class' => '.leform-element-{element-id} .leform-column-label .leform-label',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-label .leform-label',
                        ],
                        'input' => [
                            'label' => 'Input field',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input',
                        ],
                        'input-hover' => [
                            'label' => 'Input field (hover)',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input div.leform-input input:hover',
                        ],
                        'description' => [
                            'label' => 'Description',
                            'admin-class' => '.leform-element-{element-id} .leform-column-input .leform-description',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} .leform-column-input .leform-description',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'simple-mode' => ['value' => 'on', 'label' => 'Simple mode', 'tooltip' => 'If enabled, you can configure one range of values. If disabled - multiple ranges.', 'type' => 'checkbox'],
                'number' => [
                    'value' => ['value1' => '0', 'value2' => '1', 'value3' => '10', 'value4' => '1'],
                    'caption' => ['value1' => 'Min', 'value2' => 'Default', 'value3' => 'Max', 'value4' => 'Step'],
                    'label' => 'Value',
                    'tooltip' => 'Set basic parameters of number input. Min - minimum value. Max - maximum value. Default - the value that the field has before the user has entered anything. Step - increment value.',
                    'type' => 'four-numbers',
                    'visible' => ['simple-mode' => ['on']],
                ],
                'number-advanced' => [
                    'value' => ['value1' => '1', 'value2' => '0-10', 'value3' => '1'],
                    'caption' => ['value1' => 'Default', 'value2' => 'Ranges', 'value3' => 'Step'],
                    'label' => 'Value',
                    'tooltip' => 'Set basic parameters of number input. Default - the value that the field has before the user has entered anything. Step - increment value. Ranges - list of comma-separated values. Example: 0, 1...5, 7...10, 12, 14, 20...25. Important! Use triple dots to specify range.',
                    'type' => 'number-string-number',
                    'visible' => ['simple-mode' => ['off']],
                ],
                'decimal' => [
                    'value' => '0',
                    'label' => 'Decimal digits',
                    'tooltip' => 'Select the allowed number of digits after the decimal separator.',
                    'options' => ['0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '6' => '6', '8' => '8'],
                    'type' => 'select',
                ],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'readonly' => ['value' => 'off', 'label' => 'Read only', 'tooltip' => 'If enabled, the user can not edit the field value.', 'type' => 'checkbox'],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this field', 'hide' => 'Hide this field'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'validators' => [
                    'values' => [],
                    'allowed-values' => ['equal', 'equal-field', 'greater', 'in-array', 'less'],
                    'label' => 'Validators',
                    'tooltip' => 'Validators checks whether the data entered by the user is valid.',
                    'type' => 'validators',
                ],
            ],
            'hidden' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Hidden field',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'default' => ['value' => '', 'label' => 'Default value', 'tooltip' => 'The default value is the value that the field has before the user has entered anything.', 'type' => 'text'],
                'dynamic-default' => [
                    'value' => 'off',
                    'label' => 'Dynamic default value',
                    'tooltip' => 'Allows the default value of the field to be set dynamically via a URL parameter.',
                    'type' => 'checkbox',
                ],
                'dynamic-parameter' => [
                    'value' => '',
                    'label' => 'Parameter name',
                    'tooltip' => 'This is the name of the parameter that you will use to set the default value.',
                    'type' => 'text',
                    'visible' => ['dynamic-default' => ['on']],
                ],
                'save' => [
                    'value' => 'on',
                    'label' => 'Save to database',
                    'tooltip' => 'If enabled, the submitted element data will be saved to the database and shown when viewing an entry.',
                    'type' => 'checkbox',
                ],
                'advanced' => ['type' => 'tab', 'value' => 'advanced', 'label' => 'Advanced'],
                'element-id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the input field.', 'type' => 'id'],
                'xml' => ['type' => 'tab', 'value' => 'xml', 'label' => 'XML'],
                'xml-field-not-exported' => [
                    'value' => 'on',
                    'label' => 'Don’t output this element in XML',
                    'tooltip' => 'Don’t output this element in XML',
                    'type' => 'checkbox',
                ],
                'xml-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['xml-field-not-exported' => ['off']],
                ],
                'xml-field-names' => LeformService::$propertiesMetaXmlFieldNames,
                'custom-xml-fields' => LeformService::$propertiesMetaCustomXmlFields,
                'csv' => ['type' => 'tab', 'value' => 'csv', 'label' => 'CSV'],
                'display-on-csv' => [
                    'value' => 'off',
                    'label' => 'Display on csv',
                    'tooltip' => 'Display on csv',
                    'type' => 'checkbox'
                ],
                'csv-order' => [
                    'value' => '',
                    'label' => 'Order',
                    // 'tooltip' => '',
                    'type' => 'text',
                    'visible' => ['display-on-csv' => ['on']],
                ],
            ],
            'button' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => ['value' => 'Button', 'label' => 'Name', 'tooltip' => 'The name is used for your reference.', 'type' => 'text'],
                'label' => ['value' => 'Submit', 'label' => 'Label', 'tooltip' => 'This is the label of the button.', 'type' => 'text'],
                'button-type' => [
                    'value' => 'submit',
                    'label' => 'Type',
                    'tooltip' => 'Choose the type of the button.',
                    'type' => 'radio-bar',
                    'options' => ['submit' => 'Submit', 'prev' => 'Back', 'next' => 'Next'],
                ],
                'label-loading' => [
                    'value' => 'Sending...',
                    'label' => 'Sending label',
                    'type' => 'text',
                    'tooltip' => 'This is the label of the button when data are sending to server.',
                    'visible' => ['button-type' => ['submit', 'next']],
                ],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'button-style' => [
                    'value' => ['size' => '', 'width' => '', 'position' => ''],
                    'caption' => ['size' => 'Size', 'width' => 'Width', 'position' => 'Position'],
                    'label' => 'Style',
                    'tooltip' => 'Adjust the button size and position.',
                    'type' => 'local-button-style',
                ],
                'icon' => [
                    'value' => ['left' => '', 'right' => ''],
                    'caption' => ['left' => 'Left side', 'right' => 'Right side'],
                    'label' => 'Icons',
                    'tooltip' => 'These icons appear near the button label.',
                    'type' => 'button-icons',
                ],
                'colors-sections' => [
                    'type' => 'sections',
                    'sections' => [
                        'button-default' => ['label' => 'Default', 'icon' => 'fas fa-globe'],
                        'button-hover' => ['label' => 'Hover', 'icon' => 'far fa-hand-pointer'],
                        'button-active' => ['label' => 'Active', 'icon' => 'far fa-paper-plane'],
                    ],
                ],
                'start-button-default' => ['type' => 'section-start', 'section' => 'button-default'],
                'colors' => [
                    'value' => ['background' => '', 'border' => '', 'text' => ''],
                    'caption' => ['background' => 'Background', 'border' => 'Border', 'text' => 'Text'],
                    'label' => 'Colors',
                    'tooltip' => 'Adjust the colors of the button.',
                    'type' => 'colors',
                ],
                'end-button-default' => ['type' => 'section-end'],
                'start-button-hover' => ['type' => 'section-start', 'section' => 'button-hover'],
                'colors-hover' => [
                    'value' => ['background' => '', 'border' => '', 'text' => ''],
                    'caption' => ['background' => 'Background', 'border' => 'Border', 'text' => 'Text'],
                    'label' => 'Colors',
                    'tooltip' => 'Adjust the colors of the hovered button.',
                    'type' => 'colors',
                ],
                'end-button-hover' => ['type' => 'section-end'],
                'start-button-active' => ['type' => 'section-start', 'section' => 'button-active'],
                'colors-active' => [
                    'value' => ['background' => '', 'border' => '', 'text' => ''],
                    'caption' => ['background' => 'Background', 'border' => 'Border', 'text' => 'Text'],
                    'label' => 'Colors',
                    'tooltip' => 'Adjust the colors of the active button.',
                    'type' => 'colors',
                ],
                'end-button-active' => ['type' => 'section-end'],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the button.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'button' => [
                            'label' => 'Button',
                            'admin-class' => '.leform-element-{element-id} a.leform-button',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button',
                        ],
                        'button-hover' => [
                            'label' => 'Button (hover)',
                            'admin-class' => '.leform-element-{element-id} a.leform-button:hover',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button:hover',
                        ],
                        'button-active' => [
                            'label' => 'Button (active)',
                            'admin-class' => '.leform-element-{element-id} a.leform-button:active',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button:active',
                        ],
                        'button-icon-left' => [
                            'label' => 'Button icon (left)',
                            'admin-class' => '.leform-element-{element-id} a.leform-button i.leform-icon-left',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button i.leform-icon-left',
                        ],
                        'button-icon-right' => [
                            'label' => 'Button icon (right)',
                            'admin-class' => '.leform-element-{element-id} a.leform-button i.leform-icon-right',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button i.leform-icon-right',
                        ],
                    ],
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this button', 'hide' => 'Hide this button'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
            ],
            'link-button' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => [
                    'value' => 'Button',
                    'label' => 'Name',
                    'tooltip' => 'The name will be shown in place of the label throughout the plugin, in the notification email and when viewing submitted form entries.',
                    'type' => 'text',
                ],
                'label' => ['value' => 'Link', 'label' => 'Label', 'tooltip' => 'This is the label of the button.', 'type' => 'text'],
                'link' => [
                    'value' => '',
                    'label' => 'URL',
                    'tooltip' => 'Specify the URL where users redirected to.',
                    'type' => 'field-or-text',
                    'isTextarea' => false
                ],
                'bind-field' => [
                    'value' => null,
                    'type' => 'bind-field'
                ],
                'new-tab' => ['value' => 'off', 'label' => 'Open link in new tab', 'tooltip' => 'If enabled, the link will be opened in new tab.', 'type' => 'checkbox'],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'button-style' => [
                    'value' => ['size' => '', 'width' => '', 'position' => ''],
                    'caption' => ['size' => 'Size', 'width' => 'Width', 'position' => 'Position'],
                    'label' => 'Style',
                    'tooltip' => 'Adjust the button size and position).',
                    'type' => 'local-button-style',
                ],
                'icon' => [
                    'value' => ['left' => '', 'right' => ''],
                    'caption' => ['left' => 'Left side', 'right' => 'Right side'],
                    'label' => 'Icons',
                    'tooltip' => 'These icons appear near the button label.',
                    'type' => 'button-icons',
                ],
                'colors-sections' => [
                    'type' => 'sections',
                    'sections' => [
                        'button-default' => ['label' => 'Default', 'icon' => 'fas fa-globe'],
                        'button-hover' => ['label' => 'Hover', 'icon' => 'far fa-hand-pointer'],
                        'button-active' => ['label' => 'Active', 'icon' => 'far fa-paper-plane'],
                    ],
                ],
                'start-button-default' => ['type' => 'section-start', 'section' => 'button-default'],
                'colors' => [
                    'value' => ['background' => '', 'border' => '', 'text' => ''],
                    'caption' => ['background' => 'Background', 'border' => 'Border', 'text' => 'Text'],
                    'label' => 'Colors',
                    'tooltip' => 'Adjust the colors of the button.',
                    'type' => 'colors',
                ],
                'end-button-default' => ['type' => 'section-end'],
                'start-button-hover' => ['type' => 'section-start', 'section' => 'button-hover'],
                'colors-hover' => [
                    'value' => ['background' => '', 'border' => '', 'text' => ''],
                    'caption' => ['background' => 'Background', 'border' => 'Border', 'text' => 'Text'],
                    'label' => 'Colors',
                    'tooltip' => 'Adjust the colors of the hovered button.',
                    'type' => 'colors',
                ],
                'end-button-hover' => ['type' => 'section-end'],
                'start-button-active' => ['type' => 'section-start', 'section' => 'button-active'],
                'colors-active' => [
                    'value' => ['background' => '', 'border' => '', 'text' => ''],
                    'caption' => ['background' => 'Background', 'border' => 'Border', 'text' => 'Text'],
                    'label' => 'Colors',
                    'tooltip' => 'Adjust the colors of the active button.',
                    'type' => 'colors',
                ],
                'end-button-active' => ['type' => 'section-end'],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the button.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                        'button' => [
                            'label' => 'Button',
                            'admin-class' => '.leform-element-{element-id} a.leform-button',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button',
                        ],
                        'button-hover' => [
                            'label' => 'Button (hover)',
                            'admin-class' => '.leform-element-{element-id} a.leform-button:hover',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button:hover',
                        ],
                        'button-active' => [
                            'label' => 'Button (active)',
                            'admin-class' => '.leform-element-{element-id} a.leform-button:active',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button:active',
                        ],
                        'button-icon-left' => [
                            'label' => 'Button icon (left)',
                            'admin-class' => '.leform-element-{element-id} a.leform-button i.leform-icon-left',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button i.leform-icon-left',
                        ],
                        'button-icon-right' => [
                            'label' => 'Button icon (right)',
                            'admin-class' => '.leform-element-{element-id} a.leform-button i.leform-icon-right',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id} a.leform-button i.leform-icon-right',
                        ],
                    ],
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this button', 'hide' => 'Hide this button'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
            ],
            'html' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                'name' => ['value' => 'HTML Content', 'label' => 'Name', 'type' => 'text'],
                'content' => ['value' => '<p>' . 'HTML Inhalt.' . '</p>', 'label' => 'HTML', 'tooltip' => 'This is the content of HTML.', 'type' => 'html'],
                'style' => ['type' => 'tab', 'value' => 'style', 'label' => 'Style'],
                'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the button.', 'type' => 'text'],
                'css' => [
                    'type' => 'css',
                    'values' => [],
                    'label' => 'CSS styles',
                    'tooltip' => 'Once you have added a style, enter the CSS styles.',
                    'selectors' => [
                        'wrapper' => [
                            'label' => 'Wrapper',
                            'admin-class' => '.leform-element-{element-id}',
                            'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                        ],
                    ],
                ],
                'data' => ['type' => 'tab', 'value' => 'data', 'label' => 'Data'],
                'external-datasource' => [
                    'value' => 'off',
                    'label' => 'Pre-fill with content from external source',
                    'type' => 'checkbox',
                ],
                'external-datasource-url' => [
                    'value' => '',
                    'label' => 'Source for the data (URI)',
                    'type' => 'url',
                    'visible' => ['external-datasource' => ['on']],
                ],
                'external-datasource-path' => [
                    'value' => '',
                    'label' => 'Path to the object with the options',
                    'tooltip' => 'Specify path according to dot notation.',
                    'type' => 'text',
                    'visible' => ['external-datasource' => ['on']],
                ],
                'logic-tab' => ['type' => 'tab', 'value' => 'logic', 'label' => 'Logic'],
                'logic-enable' => [
                    'value' => 'off',
                    'label' => 'Enable conditional logic',
                    'tooltip' => 'If enabled, you can create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'checkbox',
                ],
                'logic' => [
                    'values' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
                    'actions' => ['show' => 'Show this element', 'hide' => 'Hide this element'],
                    'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
                    'label' => 'Logic rules',
                    'tooltip' => 'Create rules to show or hide this element depending on the values of other fields.',
                    'type' => 'logic-rules',
                    'visible' => ['logic-enable' => ['on']],
                ],
            ],
            'background-image' => [
                'basic' => ['type' => 'tab', 'value' => 'basic', 'label' => 'Basic'],
                // 'css-class' => ['value' => '', 'label' => 'Custom CSS class', 'tooltip' => 'This class name will be added to the button.', 'type' => 'text'],
                // 'css' => [
                //     'type' => 'css',
                //     'values' => [],
                //     'label' => 'CSS styles',
                //     'tooltip' => 'Once you have added a style, enter the CSS styles.',
                //     'selectors' => [
                //         'wrapper' => [
                //             'label' => 'Wrapper',
                //             'admin-class' => '.leform-element-{element-id}',
                //             'front-class' => '.leform-form-{form-id} .leform-element-{element-id}',
                //         ],
                //     ],
                // ],
                'image' => [
                    'value' => 'https://media.istockphoto.com/vectors/thumbnail-image-vector-graphic-vector-id1147544807?k=20&m=1147544807&s=612x612&w=0&h=pBhz1dkwsCMq37Udtp9sfxbjaMl27JUapoyYpQm0anc=',
                    'label' => 'Image',
                    'tooltip' => 'Background image tooltip',
                    'type' => 'image-upload'
                ],
            ]
        ];
    }

    public $validatorsMeta = [
        'alpha' => [
            'label' => 'Alpha',
            'tooltip' => 'Checks that the value contains only alphabet characters.',
            'properties' => [
                'whitespace-allowed' => [
                    'value' => 'off',
                    'label' => 'Allow whitespace',
                    'tooltip' => 'If checked, any spaces or tabs are allowed.',
                    'type' => 'checkbox'
                ],
                'error' => [
                    'value' => 'Only alphabet characters are allowed.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'alphanumeric' => [
            'label' => 'Alphanumeric',
            'tooltip' => 'Checks that the value contains only alphabet characters or digits.',
            'properties' => [
                'whitespace-allowed' => ['value' => 'off', 'label' => 'Allow whitespace', 'tooltip' => 'If checked, any spaces or tabs are allowed.', 'type' => 'checkbox'],
                'error' => [
                    'value' => 'Only alphabet characters and digits are allowed.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'date' => [
            'label' => 'Date',
            'tooltip' => 'Checks that the value is a valid date (according to pre-defined date format set on Form Settings).',
            'properties' => [
                'error' => [
                    'value' => 'Invalid date.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'digits' => [
            'label' => 'Digits',
            'tooltip' => 'Checks that the value contains only digits.',
            'properties' => [
                'whitespace-allowed' => ['value' => 'off', 'label' => 'Allow whitespace', 'tooltip' => 'If checked, any spaces or tabs are allowed.', 'type' => 'checkbox'],
                'error' => [
                    'value' => 'Only digits are allowed.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'email' => [
            'label' => 'Email',
            'tooltip' => 'Checks that the value is a valid email address.',
            'properties' => [
                'error' => [
                    'value' => 'Invalid email address.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'equal' => [
            'label' => 'Equal',
            'tooltip' => 'Checks that the value is identical to the given token.',
            'properties' => [
                'token' => ['value' => '', 'label' => 'Token', 'tooltip' => 'The token that the submitted value must be equal to.', 'type' => 'text'],
                'error' => [
                    'value' => 'The value does not match {token}.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code><br /><code>{token} = ' . 'the token' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'equal-field' => [
            'label' => 'Equal To Field',
            'tooltip' => 'Checks that the value is identical to the value of other field.',
            'properties' => [
                'token' => ['value' => '', 'label' => 'Field', 'tooltip' => 'The field that the submitted value must be equal to.', 'type' => 'field'],
                'error' => [
                    'value' => 'The value does not match.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'greater' => [
            'label' => 'Greater Than',
            'tooltip' => 'Checks that the value is numerically greater than the given minimum.',
            'properties' => [
                'min' => ['value' => '0', 'label' => 'Minimum', 'tooltip' => 'The submitted value must be numerically greater than the minimum.', 'type' => 'integer'],
                'error' => [
                    'value' => 'The value is not greater than {min}.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code><br /><code>{min} = ' . 'the minimum allowed value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'in-array' => [
            'label' => 'In Array',
            'tooltip' => 'Checks that the value is in a list of allowed values.',
            'properties' => [
                'values' => ['value' => '', 'label' => 'Allowed values', 'tooltip' => 'Enter one allowed value per line.', 'type' => 'textarea'],
                'invert' => ['value' => 'off', 'label' => 'Invert', 'tooltip' => 'Invert the check i.e. the submitted value must not be in the allowed values list.', 'type' => 'checkbox'],
                'error' => [
                    'value' => 'This value is not valid.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'length' => [
            'label' => 'Length',
            'tooltip' => 'Checks that the length of the value is between the given maximum and minimum.',
            'properties' => [
                'min' => ['value' => '0', 'label' => 'Minimum length', 'tooltip' => 'The length of the submitted value must be greater than or equal to the minimum.', 'type' => 'integer'],
                'max' => ['value' => '0', 'label' => 'Maximum length', 'tooltip' => 'The length of the submitted value must be less than or equal to the maximum.', 'type' => 'integer'],
                'error' => [
                    'value' => 'The number of characters must be in a range [{min}..{max}].',
                    'label' => 'Error message',
                    'tooltip' =>
                    'Variables:' .
                        '<br /><br /><code>{value} = ' .
                        'the submitted value' .
                        '</code><br /><code>{length} = ' .
                        'the length of the submitted value' .
                        '</code><br /><code>{min} = ' .
                        'the minimum allowed length' .
                        '</code><br /><code>{max} = ' .
                        'the maximum allowed length' .
                        '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'less' => [
            'label' => 'Less Than',
            'tooltip' => 'Checks that the value is numerically less than the given maximum.',
            'properties' => [
                'max' => ['value' => '0', 'label' => 'Maximum', 'tooltip' => 'The submitted value must be numerically less than the maximum.', 'type' => 'integer'],
                'error' => [
                    'value' => 'The value is not less than {max}.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code><br /><code>{max} = ' . 'the maximum allowed value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'prevent-duplicates' => [
            'label' => 'Prevent Duplicates',
            'tooltip' => 'Checks that the same value has not already been submitted.',
            'properties' => [
                'error' => [
                    'value' => 'This value is a duplicate of a previously submitted form.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'regex' => [
            'label' => 'Regex',
            'tooltip' => 'Checks that the value matches the given regular expression.',
            'properties' => [
                'pattern' => [
                    'value' => '',
                    'label' => 'Pattern',
                    'tooltip' => 'The submitted value must match this regular expression. The pattern should include start and end delimiters, see below for an example.' . '<br /><br /><code>/[^a-zA-Z0-9]/</code>',
                    'type' => 'text',
                ],
                'invert' => ['value' => 'off', 'label' => 'Invert', 'tooltip' => 'Invert the check i.e. the submitted value must not match the regular expression.', 'type' => 'checkbox'],
                'error' => [
                    'value' => 'Invalid value.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'time' => [
            'label' => 'Time',
            'tooltip' => 'Checks that the value is a valid time (according to pre-defined time format set on Form Settings).',
            'properties' => [
                'error' => [
                    'value' => 'Invalid time.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
        'url' => [
            'label' => 'URL',
            'tooltip' => 'Checks that the value is a valid URL.',
            'properties' => [
                'error' => [
                    'value' => 'Invalid URL.',
                    'label' => 'Error message',
                    'tooltip' => 'Variables:' . '<br /><br /><code>{value} = ' . 'the submitted value' . '</code>',
                    'type' => 'error',
                ],
            ],
        ],
    ];

    public $filtersMeta = [
        'alpha' => [
            'label' => 'Alpha',
            'tooltip' => 'Removes any non-alphabet characters.',
            'properties' => [
                'whitespace-allowed' => ['value' => 'off', 'label' => 'Allow whitespace', 'tooltip' => 'If checked, any spaces or tabs will not be stripped.', 'type' => 'checkbox'],
            ],
        ],
        'alphanumeric' => [
            'label' => 'Alphanumeric',
            'tooltip' => 'Removes any non-alphabet characters and non-digits.',
            'properties' => [
                'whitespace-allowed' => ['value' => 'off', 'label' => 'Allow whitespace', 'tooltip' => 'If checked, any spaces or tabs will not be stripped.', 'type' => 'checkbox'],
            ],
        ],
        'digits' => [
            'label' => 'Digits',
            'tooltip' => 'Removes any non-digits.',
            'properties' => [
                'whitespace-allowed' => ['value' => 'off', 'label' => 'Allow whitespace', 'tooltip' => 'If checked, any spaces or tabs will not be stripped.', 'type' => 'checkbox'],
            ],
        ],
        'regex' => [
            'label' => 'Regex',
            'tooltip' => 'Removes characters matching the given regular expression.',
            'properties' => [
                'pattern' => [
                    'value' => '',
                    'label' => 'Pattern',
                    'tooltip' => 'Any text matching this regular expression pattern will be stripped. The pattern should include start and end delimiters, see below for an example.' . '<br /><br /><code>/[^a-zA-Z0-9]/</code>',
                    'type' => 'text',
                ],
            ],
        ],
        'strip-tags' => [
            'label' => 'Strip Tags',
            'tooltip' => 'Removes any HTML tags.',
            'properties' => [
                'tags-allowed' => [
                    'value' => '',
                    'label' => 'Allowable tags',
                    'tooltip' => 'Enter allowable tags, one after the other, see below for an example.' . '<br /><br /><code>&amp;lt;p&amp;gt;&amp;lt;a&amp;gt;&amp;lt;span&amp;gt;</code>',
                    'type' => 'text',
                ],
            ],
        ],
        'trim' => [
            'label' => 'Trim',
            'tooltip' => 'Removes white space from the start and end.',
        ],
    ];

    public $confirmationsMeta = [
        'name' => ['value' => 'Confirmation', 'label' => 'Name', 'tooltip' => 'The name of the confirmation. It is used for your convenience.', 'type' => 'name'],
        'type' => [
            'value' => 'page',
            'label' => 'Type',
            'tooltip' => 'Choose the type of the confirmation.',
            'type' => 'select',
            'options' => [
                'page' => 'Display Confirmation page',
                'page-redirect' => 'Display Confirmation page and redirect to certain URL',
                'page-payment' => 'Display Confirmation page and request payment',
                'message' => 'Display Message',
                'message-redirect' => 'Display Message and redirect to certain URL',
                'message-payment' => 'Display Message and request payment',
                'redirect' => 'Redirect to certain URL',
                'payment' => 'Request payment',
            ],
        ],
        'payment-gateway' => [
            'value' => '',
            'label' => 'Payment gateway',
            'tooltip' => 'Select payment gateway. You can configure it on "Advanced" tab, "Payment Gateways" section.',
            'type' => 'text',
        ],
        'message' => [
            'value' => 'Thank you. We will contact you as soon as possible.',
            'label' => 'Message',
            'tooltip' => 'The message appears below the form after successful submission.',
            'type' => 'text',
        ],
        'url' => ['value' => 'http://localhost/forms/create', 'label' => 'URL', 'tooltip' => 'User will be redirected to this URL after successful form submission.', 'type' => 'text'],
        'delay' => ['value' => "3", 'label' => 'Delay', 'tooltip' => 'The message stay visible during this number of seconds.', 'type' => 'integer', 'unit' => 'seconds'],
        'reset-form' => ['value' => 'on', 'label' => 'Reset form to default state', 'tooltip' => 'If enabled, the form will be reset to default state.', 'type' => 'checkbox'],
        'logic-enable' => [
            'value' => 'off',
            'label' => 'Enable conditional logic',
            'tooltip' => 'If enabled, you can create rules to enable this confirmation depending on the values of input fields.',
            'type' => 'checkbox',
        ],
        'logic' => [
            'value' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
            'actions' => ['show' => 'Enable this confirmation'],
            'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
            'label' => 'Logic rules',
            'tooltip' => 'Create rules to show this confirmation depending on the values of input fields.',
            'type' => 'logic-rules',
        ],
    ];

    public $notificationsMeta = [
        'name' => ['value' => 'Notification', 'label' => 'Name', 'tooltip' => 'The name of the notification. It is used for your convenience.', 'type' => 'name'],
        'enabled' => ['value' => 'on', 'label' => 'Enabled', 'tooltip' => 'You can stop this notification being sent by turning this off.', 'type' => 'checkbox'],
        'action' => [
            'value' => 'submit',
            'label' => 'Send',
            'tooltip' => 'You can specify when notification will be sent.',
            'type' => 'select',
            'options' => [
                'submit' => 'After successful form submission',
                'confirm' => 'When user confirmed submitted data using native double opt-in feature',
                'payment-success' => 'After successfully completed payment',
                'payment-fail' => 'After non-completed payment',
            ],
        ],
        'recipient-email' => ['value' => '', 'label' => 'Recipient', 'tooltip' => 'Add email addresses (comma-separated) to which this email will be sent to.', 'type' => 'text'],
        'subject' => ['value' => 'New submission from {{form-name}}', 'label' => 'Subject', 'tooltip' => 'The subject of the email message.', 'type' => 'text'],
        'message' => ['value' => '{{form-data}}', 'label' => 'Message', 'tooltip' => 'The content of the email message.', 'type' => 'html'],
        'attachments' => ['value' => [], 'label' => 'Attachments', 'tooltip' => 'Select files that you want to attach to the email message.', 'type' => 'attachments'],
        'from' => [
            'value' => ['email' => '{{global-from-email}}', 'name' => '{{global-from-name}}'],
            'label' => 'From',
            'tooltip' => 'Sets the "From" address and name. The email address and name set here will be shown as the sender of the email.',
            'type' => 'from',
        ],
        'reply-email' => ['value' => '', 'label' => 'Reply-To', 'tooltip' => 'Add a "Reply-To" email address. If not set, replying to the email will reply to the "From" address.', 'type' => 'text'],
        'logic-enable' => ['value' => 'off', 'label' => 'Enable conditional logic', 'tooltip' => 'If enabled, you can create rules to enable this notification depending on the values of input fields.', 'type' => 'checkbox'],
        'logic' => [
            'value' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
            'actions' => ['show' => 'Enable this notification'],
            'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
            'label' => 'Logic rules',
            'tooltip' => 'Create rules to show this notification depending on the values of input fields.',
            'type' => 'logic-rules',
        ],
    ];

    public $integrationsMeta = [
        'name' => ['value' => '', 'label' => 'Name', 'tooltip' => 'The name of the integration. It is used for your convenience.', 'type' => 'name'],
        'enabled' => ['value' => 'on', 'label' => 'Enabled', 'tooltip' => 'You can disable this integration by turning this off.', 'type' => 'checkbox'],
        'action' => [
            'value' => 'submit',
            'label' => 'Execute',
            'tooltip' => 'You can specify when integration will be executed.',
            'type' => 'select',
            'options' => [
                'submit' => 'After successful form submission',
                'confirm' => 'When user confirmed submitted data using native double opt-in feature',
                'payment-success' => 'After successfully completed payment',
                'payment-fail' => 'After non-completed payment',
            ],
        ],
        'logic-enable' => ['value' => 'off', 'label' => 'Enable conditional logic', 'tooltip' => 'If enabled, you can create rules to enable this integration depending on the values of input fields.', 'type' => 'checkbox'],
        'logic' => [
            'value' => ['action' => 'show', 'operator' => 'and', 'rules' => []],
            'actions' => ['show' => 'Enable this integration'],
            'operators' => ['and' => 'if all of these rules match', 'or' => 'if any of these rules match'],
            'label' => 'Logic rules',
            'tooltip' => 'Create rules to enable this integration depending on the values of input fields.',
            'type' => 'logic-rules',
        ],
    ];

    public $paymentGatewaysMeta = [
        'id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the payment gateway.', 'type' => 'id'],
        'name' => ['value' => '', 'label' => 'Name', 'tooltip' => 'The name of the payment gateway. It is used for your convenience.', 'type' => 'name'],
    ];

    public $mathMeta = [
        'id' => ['value' => '', 'label' => 'ID', 'tooltip' => 'The unique ID of the expression.', 'type' => 'id'],
        'name' => ['value' => 'Expression', 'label' => 'Name', 'tooltip' => 'The name of the expression. It is used for your convenience.', 'type' => 'name'],
        'expression' => ['value' => '', 'label' => 'Expression', 'tooltip' => 'Type math expression here. Use basic arithmetic operators:' . ' <code>-, +, *, /</code>.', 'type' => 'text'],
        'default' => ['value' => '0', 'label' => 'Default', 'tooltip' => 'This value is used if expression can not be calculated (for example, in case of division by zero, typos, missed variables, non-numeric values, etc.).', 'type' => 'text'],
        'decimal-digits' => ['value' => "2", 'label' => 'Decimal digits', 'tooltip' => 'Specify how many decimal digits the result must have.', 'type' => 'integer'],
    ];

    public $logicRules = [
        'is' => 'is',
        'is-not' => 'is not',
        'is-empty' => 'is empty',
        'is-not-empty' => 'is not empty',
        'is-greater' => 'is greater than',
        'is-less' => 'is less than',
        'contains' => 'contains',
        'starts-with' => 'starts with',
        'ends-with' => 'ends with'
    ];

    public function getDefaultFormOptions($type = 'settings')
    {
        $formOptions = [];

        if (!array_key_exists($type, $this->getElementPropertiesMeta())) {
            return [];
        }

        foreach ($this->getElementPropertiesMeta()[$type] as $key => $value) {
            if (array_key_exists('value', $value)) {
                if (is_array($value['value'])) {
                    foreach ($value['value'] as $optionKey => $optionValue) {
                        $formOptions[$key . '-' . $optionKey] = $optionValue;
                    }
                } else {
                    $formOptions[$key] = $value['value'];
                }
            } else if (array_key_exists('values', $value)) {
                $formOptions[$key] = $value['values'];
            }
        }

        return $formOptions;
    }

    static function random_string($_length = 16)
    {
        $symbols = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = "";
        for ($i = 0; $i < $_length; $i++) {
            $string .= $symbols[rand(0, strlen($symbols) - 1)];
        }
        return $string;
    }

    public $advanced_options = [
        'enable-custom-js' => 'off',
        'enable-htmlform' => 'off',
        'enable-post' => 'off',
        'enable-mysql' => 'off',
        'enable-wpuser' => 'off',
        'enable-acellemail' => 'off',
        'enable-activecampaign' => 'off',
        'enable-activetrail' => 'off',
        'enable-agilecrm' => 'off',
        'enable-automizy' => 'off',
        'enable-avangemail' => 'off',
        'enable-authorizenet' => 'off',
        'enable-aweber' => 'off',
        'enable-birdsend' => 'off',
        'enable-bitrix24' => 'off',
        'enable-campaignmonitor' => 'off',
        'enable-cleverreach' => 'off',
        'enable-constantcontact' => 'off',
        'enable-conversio' => 'off',
        'enable-drip' => 'off',
        'enable-fluentcrm' => 'off',
        'enable-freshmail' => 'off',
        'enable-getresponse' => 'off',
        'enable-hubspot' => 'off',
        'enable-inbox' => 'off',
        'enable-jetpack' => 'off',
        'enable-klaviyo' => 'off',
        'enable-madmimi' => 'off',
        'enable-mailautic' => 'off',
        'enable-mailchimp' => 'on',
        'enable-mailerlite' => 'off',
        'enable-mailfit' => 'off',
        'enable-mailgun' => 'off',
        'enable-mailjet' => 'off',
        'enable-mailpoet' => 'off',
        'enable-mailster' => 'off',
        'enable-mailwizz' => 'off',
        'enable-mautic' => 'off',
        'enable-moosend' => 'off',
        'enable-mumara' => 'off',
        'enable-omnisend' => 'off',
        'enable-ontraport' => 'off',
        'enable-rapidmail' => 'off',
        'enable-salesautopilot' => 'off',
        'enable-sendfox' => 'off',
        'enable-sendgrid' => 'off',
        'enable-sendinblue' => 'off',
        'enable-sendpulse' => 'off',
        'enable-sendy' => 'off',
        'enable-thenewsletterplugin' => 'off',
        'enable-tribulant' => 'off',
        'enable-ymlp' => 'off',
        'enable-zapier' => 'off',
        'enable-zohocrm' => 'off',
        'enable-blockchain' => 'off',
        'enable-instamojo' => 'off',
        'enable-interkassa' => 'off',
        'enable-mollie' => 'off',
        'enable-payfast' => 'off',
        'enable-paypal' => 'off',
        'enable-paystack' => 'off',
        'enable-payumoney' => 'off',
        'enable-perfectmoney' => 'off',
        'enable-razorpay' => 'off',
        'enable-skrill' => 'off',
        'enable-stripe' => 'off',
        'enable-wepay' => 'off',
        'enable-yandexmoney' => 'off',
        'enable-bulkgate' => 'off',
        'enable-gatewayapi' => 'off',
        'enable-nexmo' => 'off',
        'enable-twilio' => 'off',
        'enable-clearout' => 'off',
        'enable-kickbox' => 'off',
        'enable-thechecker' => 'on',
        'enable-truemail' => 'off',
        'minified-sources' => 'on',
        'admin-menu-stats' => 'on',
        'admin-menu-analytics' => 'on',
        'admin-menu-transactions' => 'on',
        'important-enable' => 'off',
        'custom-fonts' => ''
    ];

    public function validate_email($_email, $_advanced = false)
    {
        if (!preg_match("/^[_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,19})$/i", $_email)) {
            return false;
        }
        if (!$_advanced) {
            return true;
        }
        if ($this->options['email-validator'] == 'basic') {
            return true;
        }

        #$validation = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."leform_validations WHERE created > '".(time()-1800)."' AND type = 'email' AND hash = '".esc_sql(md5(strtolower($_email)))."'", ARRAY_A);
        $validation = Validation::where('created', '>', time() - 1800)
            ->where('type', 'email')
            ->where('hash', md5(strtolower($_email)))
            ->first();

        if (!empty($validation)) {
            return $validation['valid'] == 1;
        }

        $result = true;
        if ($this->options['email-validator'] == 'advanced') {
            if (filter_var($_email, FILTER_VALIDATE_EMAIL) === false) {
                return false;
            }
            $domain = explode("@", $_email, 2);
            $result = (checkdnsrr($domain[1]) ? true : false);
        } else {
            // $result = apply_filters('leform_validate_email_do_'.$this->options['email-validator'], true, $_email);
        }

        #$wpdb->query("INSERT INTO ".$wpdb->prefix."leform_validations (type,hash,valid,created) VALUES ( 'email', '".esc_sql(md5(strtolower($_email)))."', '".esc_sql($result ? '1' : '0')."', '".time()."')");
        Validation::create([
            'type' => 'email',
            'hash' => md5(strtolower($_email)),
            'valid' => $result ? '1' : '0',
            'created' => time(),
        ]);

        return $result;
    }

    public function validate_time($_time, $_format = 'H:i')
    {
        $replacements = [
            'hh:ii' => 'H:i',
            'hh:ii aa' => 'h:i a'
        ];
        if (array_key_exists($_format, $replacements)) {
            $_format = $replacements[$_format];
        }
        $time = DateTime::createFromFormat('Y-m-d ' . $_format, '2020-01-01 ' . $_time);
        if ($time && $time->format($_format) === $_time) {
            return $time;
        }
        return false;
    }

    public function validate_date($_date, $_format = 'Y-m-d')
    {
        $replacements = [
            'yyyy-mm-dd' => 'Y-m-d',
            'dd/mm/yyyy' => 'd/m/Y',
            'mm/dd/yyyy' => 'm/d/Y',
            'dd.mm.yyyy' => 'd.m.Y'
        ];
        if (array_key_exists($_format, $replacements)) {
            $_format = $replacements[$_format];
        }
        $date = DateTime::createFromFormat($_format, $_date);
        if ($date && $date->format($_format) === $_date) {
            return $date;
        }
        return false;
    }

    function unixtime_string($_time, $_format = "Y-m-d H:i")
    {
        return date($_format, $_time + 3600 * $this->gmt_offset);
    }

    function get_info_label($_key)
    {
        $label = '-';
        if ($_key == 'ip') $label = 'IP Address';
        else if ($_key == 'url') $label = 'Form URL';
        else if ($_key == 'page-title') $label = 'Page Title';
        else if ($_key == 'user-agent') $label = 'User Agent';
        else if ($_key == 'record-id') $label = 'Record ID';
        else if ($_key == 'wp-user-login') $label = 'WP User Login';
        else if ($_key == 'wp-user-email') $label = 'WP User Email';
        return $label;
    }

    public static function renderMatrixElement($field, $values)
    {
        $topCols = "";
        foreach ($field['top'] as $tolCol) {
            $topCols .= "<div class='pb-3'>" . $tolCol['label'] . "</div>";
        }
        $topCols = "
            <div class='grid grid-cols-" . (count($field['top']) + 2) . "'>
                <div class='col-span-2'></div>
                $topCols
            </div>
        ";

        $isCheckbox = $field['multi-select'] === 'on';

        $bodyCols = "";
        foreach ($field['left'] as $leftCol) {
            $row = "";
            foreach ($field['top'] as $topCol) {
                $checkboxValue = $leftCol['value'] . "--" . $topCol['value'];

                $selected = "";
                if ($values) {
                    $selected = in_array($checkboxValue, $values) ? 'checked' : '';
                }

                $classlist = "";
                $inputType = ($isCheckbox) ? 'checkbox' : 'radio';

                if ($isCheckbox) {
                    $classlist = implode(" ", [
                        "leform-checkbox",
                        "leform-checkbox-fa-check",
                        "leform-checkbox-medium",
                    ]);
                } else {
                    $classlist = implode(" ", [
                        "leform-radio",
                        "leform-radio-fa-check",
                        "leform-radio-medium",
                    ]);
                }

                $row .= "
                    <div class='leform-cr-box pb-2'>
                        <input
                            class='$classlist'
                            type='$inputType'
                            name='value[]'
                            id='$checkboxValue'
                            value='$checkboxValue'
                            disabled
                            $selected
                        >
                        <label for='$checkboxValue'></label>
                    </div>
                ";
            }
            $row = "
                <form class='grid grid-cols-" . (count($field['top']) + 2) . "'>
                    <div class='col-span-2'>" . $leftCol['label'] . "</div>
                    $row
                </form>
            ";
            $bodyCols .= $row;
        }

        return $topCols . $bodyCols;
    }

    public static function shouldHideRepeaterFieldInEntries($field)
    {
        return in_array($field["type"], ["html", "link-button"]);
    }

    public static function renderRepeaterInput($input, $values)
    {
        $content = "";
        $head = "";
        $body = "";

        if (gettype($values) !== "array") {
            return "-";
        }

        foreach ($input["fields"] as $field) {
            if (LeformService::shouldHideRepeaterFieldInEntries($field)) {
                continue;
            }
            $head .= "<th class='border-2 border-gray-400 px-2 py-1'>" . $field["name"] . "</th>";
        }
        $head = "
            <thead>
                <tr>
                    <th class='border-2 border-gray-400 px-2 py-1'></th>
                    $head
                </tr>
            </thead>
        ";

        for ($i = 0; $i < count($values); $i++) {
            $row = "";
            for ($j = 0; $j < count($input["fields"]); $j++) {
                if (LeformService::shouldHideRepeaterFieldInEntries($input["fields"][$j])) {
                    continue;
                }

                if (
                    array_key_exists($i, $values)
                    && array_key_exists($j, $values[$i])
                ) {
                    $row .= "<td class='border-2 border-gray-400 px-2 py-1'>" . $values[$i][$j] . "</td>";
                } else {
                    $row .= "<td class='border-2 border-gray-400 px-2 py-1'></td>";
                }
            }
            $row = "
                <tr>
                    <td class='border-2 border-gray-400 px-2 py-1'>
                        " . ($i + 1) . "
                    </td>
                    $row
                </tr>
            ";
            $body .= $row;
        }

        return "
            <table>
                $head
                $body
            </table>
        ";
    }

    public static function renderIbanInput(
        $field,
        $values,
        $showPlaceholder = true,
        $allVariables,
        $bindedFieldsAttribute = '',
        $depsAttribute = ''
    ) {
        $defaultIban = replaceWithPredefinedValues($field["default"], $allVariables);
        $defaultBic = replaceWithPredefinedValues($field["defaultbic"], $allVariables);
        $field = (object) $field;
        $id = $field->id;
        $type = $field->type;
        $iban = isset($values['iban']) && !empty($values['iban']) ? $values['iban'] : $defaultIban;
        $bic = isset($values['bic']) && !empty($values['bic']) ? $values['bic'] : $defaultBic;
        $placeholder = $showPlaceholder ? 'DE____________________' : '';
        return "
            <div
                id='leform-element-$id'
                class='leform-element-$id leform-element leform-element-label-top'
                data-type='$type'
                data-id='$id'
                $bindedFieldsAttribute
            >
                <div class=' leform-row'>
                    <div class='leform-col leform-col-7 iban-container' style='padding-right: 5px;'>
                        <div style='min-height: 60px;'>
                            <div data-type='text' style='position: relative; left: 0px; top: 0px;'>
                                <div class='leform-column-label'>
                                    <label class='leform-label'>IBAN</label>
                                </div>
                                <div class='leform-column-input'>
                                    <div class='leform-input'>
                                        <input
                                            name='ibanInput[leform-$id][iban]'
                                            oninput='leform_input_changed(this);'
                                            onfocus='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'
                                            type='text'
                                            class='iban-input
                                            leform-mask'
                                            placeholder='$placeholder'
                                            data-default='$defaultIban'
                                            data-xmask='SS00000000000000000000'
                                            value='$iban'
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='leform-col leform-col-5 bic-container' style='padding-left: 5px;'>
                        <div style='min-height: 60px;'>
                            <div style='position: relative; left: 0px; top: 0px;'>
                                <div class='leform-column-label'>
                                    <label class='leform-label'>BIC</label>
                                </div>
                                <div class='leform-column-input'>
                                    <div class='leform-input'>
                                        <input
                                            name='ibanInput[leform-$id][bic]'
                                            readonly
                                            type='text'
                                            class='bic-input'
                                            placeholder=''
                                            data-default='$defaultBic'
                                            value='$bic'
                                            onfocus='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }

    function getMapOfDynamicVariables($elements, $pageIds, $predefinedValues)
    {
        $allVariables = evo_get_all_variables($predefinedValues);
        $predefinedValuesInheritanceMap = [];
        foreach ($elements as $element) {
            if ($element["type"] === "columns") {
                if ($element["has-dynamic-values"] === "on") {
                    $dynamicValueKey = $element["dynamic-value"];
                    $dynamicValueIndex = (intval($element["dynamic-value-index"]) - 1);
                    if (
                        array_key_exists($dynamicValueKey, $allVariables)
                        && array_key_exists(
                            $dynamicValueIndex,
                            $allVariables[$dynamicValueKey]
                        )
                    ) {
                        $predefinedValuesInheritanceMap[$element["id"]] = $allVariables[$dynamicValueKey][$dynamicValueIndex];
                    }
                } else if (!in_array($element["_parent"], $pageIds)) {
                    $predefinedValuesInheritanceMap[$element["id"]] = $element["_parent"];
                }
            }
        }
        return $predefinedValuesInheritanceMap;
    }

    function getPredefinedValuesforId($map, $parentId)
    {
        if (!array_key_exists($parentId, $map)) {
            return null;
        }

        $predefinedValues = $map[$parentId];
        switch (gettype($predefinedValues)) {
            case "string": // just to be sure cuz im tired of bugs...
            case "double": // just to be sure cuz im tired of bugs...
            case "integer":
                return $this->getPredefinedValuesforId(
                    $map,
                    $predefinedValues
                );
            case "array":
                return $predefinedValues;
            default:
                return null;
        }
    }

    function getFormPageIds($form_object)
    {
        $pageIds = [];
        foreach ($form_object->form_pages as $page) {
            $pageIds[] = $page["id"];
        }
        return $pageIds;
    }

    function log_record_details_html($_id, $_pdf = false)
    {
        $record_id = null;
        if (!empty($_id)) {
            $record_id = intval($_id);
            #$record_details = $wpdb->get_row( "SELECT t1.*, t2.name AS form_name, t2.options AS form_options, t2.elements AS form_elements FROM ".$wpdb->prefix."leform_records t1 LEFT JOIN ".$wpdb->prefix."leform_forms t2 ON t2.id = t1.form_id WHERE t1.deleted = '0' AND t1.id = '".esc_sql($record_id)."'" , ARRAY_A);
            $record_details = Record::with('form')
                ->where('deleted', 0)
                ->where('id', $record_id)
                ->first();

            if (empty($record_details)) {
                $record_id = null;
            }
        }
        if (empty($record_id)) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Requested record not found.'),
            ];
            return $return_data;
        }

        $form_object = new LeformFormService($record_details['form_id'], false, true);

        if (empty($form_object->id)) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Requested form doesn not exists.'),
            ];
            return $return_data;
        }

        $fields = json_decode($record_details['fields'], true);
        if (!is_array($fields)) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Requested record corrupted.'),
            ];
            return $return_data;
        }

        $fields_meta = [];
        $form_elements = $form_object->input_fields_sort();
        foreach ($form_elements as $form_element) {
            if (
                is_array($form_element)
                && array_key_exists('name', $form_element)
            ) {
                $fields_meta[$form_element['id']] = $form_element;
            }
        }

        $predefinedValues = json_decode(
            $record_details->predefined_values,
            true
        );
        $allVariables = evo_get_all_variables($predefinedValues);
        $pageIds = $this->getFormPageIds($form_object);
        $html = '<div class="leform-record-details" data-id="' . $record_details['id'] . '">';
        if (sizeof($fields) > 0) {
            $html .= '
            <h3>' . (!empty($this->advanced_options['label-form-values'])
                ? $this->advanced_options['label-form-values']
                : __('Form Values')
            ) . '</h3>
            <table class="leform-record-details-table">';
            #$upload_dir = wp_upload_dir(); won't be needed anymore
            $current_page_id = 0;

            $predefinedValuesInheritanceMap = $this->getMapOfDynamicVariables(
                $form_object->form_elements,
                $pageIds,
                $allVariables
            );

            foreach ($fields_meta as $id => $field_meta) {
                $fieldAccessiblePredefinedValues = $this->getPredefinedValuesforId(
                    $predefinedValuesInheritanceMap,
                    $field_meta["_parent"]
                );
                if ($fieldAccessiblePredefinedValues == null) {
                    $fieldAccessiblePredefinedValues = $allVariables;
                }

                if (array_key_exists($id, $fields)) {
                    if (sizeof($form_object->form_pages) > 2 && $current_page_id != $field_meta['page-id']) {
                        $html .= '
                            </table>
                            <h4>' . $field_meta['page-name'] . '</h4>
                            <table class="leform-record-details-table">
                        ';
                        $current_page_id = $field_meta['page-id'];
                    }
                    $values = $fields[$id];

                    if ($field_meta['type'] == 'file') {
                        if (!empty($values)) {
                            foreach ($values as $values_key => $values_value) {
                                $values[$values_key] = $values_value;
                            }
                            #$uploads = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."leform_uploads WHERE id IN ('".implode("', '", $values)."')" , ARRAY_A);
                            $uploads = Upload::whereIn('id', $values)->get();

                            $values = [];
                            foreach ($uploads as $upload_details) {
                                # check if file exists
                                if (Storage::exists('public/uploads/' . $upload_details['filename'])) {
                                    $values[] = '<a href="' .
                                        #asset("uploads/".$upload_details["filename"])
                                        Storage::url("public/uploads/" . $upload_details["filename"])
                                        . '" target="_blank">' . $upload_details['filename_original'] . '</a>';
                                } else {
                                    $values[] = $upload_details['filename_original'] . ' (' . __('file deleted') . ')';
                                }
                            }
                            if (!empty($values)) {
                                $value = implode("<br />", $values);
                            } else {
                                $value = '-';
                            }
                        } else {
                            $value = '-';
                        }
                    } else if ($field_meta['type'] == 'signature') {
                        if (empty($values)) {
                            $value = '-';
                        } else {
                            $filePath = str_replace("/storage", "", $values);
                            $fileName = str_replace("/signatures/", "", $filePath);
                            $signatureFileExists = Storage::disk("public")
                                ->exists($filePath);
                            if ($signatureFileExists) {
                                $url = Storage::url($filePath);
                                $value = "
                                    <a href='$url' target='_blank'>
                                        $fileName
                                    </a>
                                ";
                            } else {
                                $value = "$fileName (" . __("file deleted") . ")";
                            }
                        }
                        /*
                        if (substr($values, 0, strlen('data:image/png;base64,')) != 'data:image/png;base64,') {
                            $value = '-';
                        } else {
                            if ($_pdf) {
                                $value = '<img src="@'.preg_replace('#^data:image/[^;]+;base64,#', '', $values).'" />';
                            } else {
                                $value = '<img class="leform-signature-view" src="'.$values.'" alt="" />';
                            }
                        }
                        */
                    } else if ($field_meta['type'] == 'rangeslider') {
                        $value = str_replace(':', ' ... ', $values);
                    } else if ($field_meta['type'] == 'matrix') {
                        $value = LeformService::renderMatrixElement($field_meta, $values);
                    } else if ($field_meta['type'] == 'repeater-input') {
                        $value = LeformService::renderRepeaterInput($field_meta, $values);
                    } else if ($field_meta['type'] == 'iban-input') {
                        $value = LeformService::renderIbanInput($field_meta, $values, false, $allVariables);
                    } else if (
                        in_array(
                            $field_meta['type'],
                            ['select', 'radio', 'checkbox', 'multiselect', 'imageselect', 'tile']
                        )
                    ) {
                        $esc_array = [];
                        foreach ((array)$values as $key => $values_value) {
                            $added = false;
                            foreach ($field_meta['options'] as $option) {
                                if (
                                    $option['value'] == $values_value
                                    && $option['value'] != $option['label']
                                ) {
                                    $added = true;
                                    $esc_array[] = $option['label'] . ' (' . $option['value'] . ')';
                                }
                            }
                            if (!$added) {
                                $esc_array[] = $values_value;
                            }
                        }
                        $value = implode('<br />', $esc_array);
                    } else if (is_array($values)) {
                        $arrayValues = [];
                        foreach ($values as $key => $values_value) {
                            if (is_array($values_value)) {
                                foreach ($values_value as $nested_key => $nested_values_value) {
                                    $nested_values_value = trim($nested_values_value);
                                    if ($values_value == "") {
                                        $arrayValues[$key . $nested_key] = "-";
                                    } else {
                                        $arrayValues[$key . $nested_key] = $nested_values_value;
                                    }
                                }
                            } else {
                                $values_value = trim($values_value);
                                if ($values_value == "") {
                                    $arrayValues[$key] = "-";
                                } else {
                                    $arrayValues[$key] = $values_value;
                                }
                            }
                        }
                        $value = implode("<br />", $arrayValues);
                    } else if ($values != "") {
                        if ($field_meta['type'] == 'textarea') {
                            $value_strings = explode("\n", $values);
                            foreach ($value_strings as $key => $values_value) {
                                $value_strings[$key] = trim($values_value);
                            }
                            $value = implode("<br />", $value_strings);
                        } else {
                            $value = $values;
                        }
                    } else {
                        $value = "-";
                    }
                    $toolbar = '';
                    if (!$_pdf) {
                        $allow_edit = true;
                        if (in_array($field_meta['type'], ['signature', 'file'])) {
                            $allow_edit = false;
                        }
                        $toolbar = '<div class="leform-record-details-toolbar">' .
                            ($allow_edit
                                ? '<span onclick="return leform_record_field_load_editor(this);"><i class="fas fa-pencil-alt"></i></span>'
                                : ''
                            )
                            . '<span onclick="return leform_record_field_empty(this);"><i class="fas fa-eraser"></i></span><span onclick="return leform_record_field_remove(this);"><i class="far fa-trash-alt"></i></span></div><div class="leform-record-field-editor"></div>';
                    }
                    $html .= '
                        <tr>
                            <td class="leform-record-details-table-name"' . ($_pdf ? ' style="width:33%;"' : '') . '>'
                        . replaceWithPredefinedValues($field_meta['name'], $fieldAccessiblePredefinedValues)
                        . '</td>
                            <td class="leform-record-details-table-value" data-id="' . $id . '" data-type="' . $field_meta['type'] . '"' . ($_pdf ? ' style="width:67%;"' : '') . '>
                                ' . $toolbar . '
                                <div class="leform-record-field-value">' . $value . '</div>
                            </td>
                        </tr>
                    ';
                }
                unset($fields[$id]);
            }
            foreach ($fields as $id => $values) {
                if (!empty($values)) {
                    if (is_array($values)) {
                        foreach ($values as $key => $values_value) {
                            if(is_array($values_value)) {
                                $values_value = implode(' | ', $values_value);
                            }
                            $values_value = trim($values_value);
                            if ($values_value == "") {
                                $values[$key] = "-";
                            } else {
                                $values[$key] = $values_value;
                            }
                        }
                        $value = implode("<br />", $values);
                    } else {
                        if (substr($values, 0, strlen('data:image/png;base64,')) == 'data:image/png;base64,') {
                            if ($_pdf) {
                                $value = '<img src="@' . preg_replace('#^data:image/[^;]+;base64,#', '', $values) . '" />';
                            } else {
                                $value = '<img class="leform-signature-view" src="' . $values . '" alt="" />';
                            }
                        } else {
                            $value = str_replace("\n", "<br />", $values);
                        }
                    }
                    $toolbar = '';
                    if (!$_pdf) {
                        $toolbar = '<div class="leform-record-details-toolbar"><span onclick="return leform_record_field_empty(this);"><i class="fas fa-eraser"></i></span><span onclick="return leform_record_field_remove(this);"><i class="far fa-trash-alt"></i></span></div><div class="leform-record-field-editor"></div>';
                    }
                    $html .= '
                <tr><td class="leform-record-details-table-name"' . ($_pdf ? ' style="width:33%;"' : '') . '>Deleted field (ID: ' . $id . ')</td><td class="leform-record-details-table-value" data-id="' . $id . '" data-type=""' . ($_pdf ? ' style="width:67%;"' : '') . '>' . $toolbar . '<div class="leform-record-field-value">' . $value . '</div></td></tr>';
                }
            }
            $html .= '</table>';
        }
        if ($record_details['amount'] > 0) {
            $html .= '<h3>' .
                (!empty($this->advanced_options['label-payment'])
                    ? $this->advanced_options['label-payment']
                    : 'Payment'
                )
                . '</h3>
            <table class="leform-record-details-table">
                <tr><td class="leform-record-details-table-name"' . ($_pdf ? ' style="width:33%;"' : '') . '>Amount</td><td class="leform-record-details-table-value"' . ($_pdf ? ' style="width:67%;"' : '') . '>' . ($record_details['currency'] != 'BTC' ? number_format($record_details['amount'], 2, '.', '') : number_format($record_details['amount'], 8, '.', '')) . ' ' . $record_details['currency'] . '</td></tr>
                <tr><td class="leform-record-details-table-name"' . ($_pdf ? ' style="width:33%;"' : '') . '>Status</td><td class="leform-record-details-table-value"' . ($_pdf ? ' style="width:67%;"' : '') . '>' . ($record_details['status'] == 4 ? '<span class="leform-badge leform-badge-success">Paid</span>' : '<span class="leform-badge leform-badge-danger">Unpaid</span>') . '</td></tr>
            </table>';
        }
        $info = json_decode($record_details['info'], true);
        if (is_array($info) && $form_object->form_options['misc-record-tech-info'] == 'on') {
            $html .= '
            <h3>' . (!empty($this->advanced_options['label-technical-info']) ? $this->advanced_options['label-technical-info'] : 'Technical Info') . '</h3>
            <table class="leform-record-details-table">';
            $html .= '
                <tr><td class="leform-record-details-table-name"' . ($_pdf ? ' style="width:33%;"' : '') . '>Record ID</td><td class="leform-record-details-table-value"' . ($_pdf ? ' style="width:67%;"' : '') . '>' . $record_details['id'] . '</td></tr>
                <tr><td class="leform-record-details-table-name"' . ($_pdf ? ' style="width:33%;"' : '') . '>Form</td><td class="leform-record-details-table-value"' . ($_pdf ? ' style="width:67%;"' : '') . '>' . $record_details['form_name'] . ' (ID: ' . $record_details['form_id'] . ')' . '</td></tr>
                <tr><td class="leform-record-details-table-name"' . ($_pdf ? ' style="width:33%;"' : '') . '>Created</td><td class="leform-record-details-table-value"' . ($_pdf ? ' style="width:67%;"' : '') . '>' . $this->unixtime_string($record_details['created']) . '</td></tr>';
            foreach ($info as $info_key => $info_value) {
                if (!empty($info_value)) {
                    $label = $this->get_info_label($info_key);
                    $html .= '
                <tr><td class="leform-record-details-table-name"' . ($_pdf ? ' style="width:33%;"' : '') . '>' . $label . '</td><td class="leform-record-details-table-value"' . ($_pdf ? ' style="width:67%;"' : '') . '>' . $info_value . '</td></tr>';
                }
            }
            $html .= '
            </table>';
        }
        $html .= '</div>';
        $return_data = [
            'status' => 'OK',
            'html' => $html,
            'form_name' => $record_details['form_name'],
            'record-id' => $record_details['id'],
        ];
        return $return_data;
    }

    function uploads_delete($_record_id, $_element_id = null)
    {
        #$uploads = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."leform_uploads WHERE deleted = '0' AND record_id = '".esc_sql($_record_id)."'".  (!empty($_element_id) ? " AND element_id = '".esc_sql($_element_id)."'" : "")." AND upload_id = '' AND str_id = ''", ARRAY_A);
        $uploads = [];
        if (!empty($_element_id)) {
            $uploads = Upload::where('deleted', 0)
                ->where('record_id', $_record_id)
                ->where('element_id', $_element_id)
                ->where('upload_id', '')
                ->where('str_id', '')
                ->get();
        } else {
            $uploads = Upload::where('deleted', 0)
                ->where('record_id', $_record_id)
                ->where('upload_id', '')
                ->where('str_id', '')
                ->get();
        }

        foreach ($uploads as $upload) {
            Storage::delete($upload['filename']);
        }

        #$wpdb->query(" UPDATE ".$wpdb->prefix."leform_uploads SET deleted = '1' WHERE deleted = '0' AND record_id = '".esc_sql($_record_id)."'".  (!empty($_element_id) ? " AND element_id = '".esc_sql($_element_id)."'" : "")." AND upload_id = '' AND str_id = '' ");
        if (!empty($_element_id)) {
            Upload::where('deleted', 0)
                ->where('record_id', $_record_id)
                ->where('element_id', $_element_id)
                ->where('upload_id', '')
                ->where('str_id', '')
                ->update(['deleted' => 1, 'deleted_at' => now()]);
        } else {
            Upload::where('deleted', 0)
                ->where('record_id', $_record_id)
                ->where('upload_id', '')
                ->where('str_id', '')
                ->update(['deleted' => 1, 'deleted_at' => now()]);
        }
    }
    function delete_generated_files($record)
    {
        if (!empty($record['xml_file_name'])) {
            Storage::disk('private')->delete($record['xml_file_name']);
        }
        if (!empty($record['csv_file_name'])) {
            Storage::disk('private')->delete($record['csv_file_name']);
        }
        if (!empty($record['custom_report_file_name'])) {
            Storage::disk('private')->delete($record['custom_report_file_name']);
        }
        // update record files
        Record::where('id', $record['id'])->update([
            'xml_file_name' => null,
            'csv_file_name' => null,
            'custom_report_file_name' => null
        ]);
    }
    public function get_styles()
    {
        $output = [];
        $leform_native_styles = [];

        foreach ($this->leform_native_styles as $key => $style) {
            $output[] = [
                'id' => $key,
                'name' => $style['name'],
                'type' => 1, # LEFORM_STYLE_TYPE_NATIVE
            ];
        }

        # $styles = $wpdb->get_results("SELECT id, name, type FROM ".$wpdb->prefix."leform_styles WHERE deleted = '0' ORDER BY type DESC, name ASC", ARRAY_A);
        $styles = Style::where('deleted', 0)
            ->orderBy('type', 'desc')
            ->orderBy('name', 'asc')
            ->get()
            ->toArray();

        if (!empty($styles)) {
            $output = array_merge($output, $styles);
        }

        return $output;
    }
}
