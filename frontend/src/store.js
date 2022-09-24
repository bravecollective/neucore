import { reactive, readonly } from 'vue';

const state = reactive({

    /**
     * Settings data.
     *
     * Note: the properties defined here are not actually used, the complete object is replaced with
     * data based on the API call /settings/system/list.
     *
     * @var {object}
     */
    settings: {
        esiDataSource: '',
        esiHost: '',

        repository: '',
        discord: '',

        // Customization
        customization_document_title: '',
        customization_website: '',
        customization_nav_title: '',
        customization_nav_logo: '',
        customization_home_headline: '',
        customization_home_description: '',
        customization_home_logo: '',
        customization_login_text: '',
        customization_home_markdown: '',
        customization_footer_text: '',

        // Features
        groups_require_valid_token: '',
        account_deactivation_delay: '',
        account_deactivation_active_days: '',
        account_deactivation_alliances: '',
        account_deactivation_corporations: '',
        api_rate_limit_max_requests: '',
        api_rate_limit_reset_time: '',
        api_rate_limit_active: '',
        allow_login_managed: '',
        allow_character_deletion: '',
        fetch_structure_name_error_days: '',

        // EVE Mails
        mail_character: '',
        mail_invalid_token_active: '',
        mail_invalid_token_subject: '',
        mail_invalid_token_body: '',
        mail_invalid_token_alliances: '',
        mail_invalid_token_corporations: '',
        mail_missing_character_active: '',
        mail_missing_character_resend: '',
        mail_missing_character_subject: '',
        mail_missing_character_body: '',
        mail_missing_character_corporations: '',

        navigationShowGroups: '',
        navigationServices: [],
    },

    loadingCount: 0,

});

export default {
    state: readonly(state),

    /**
     * @param {object} settings
     */
    setSettings(settings) {
        state.settings = settings;
    },

    increaseLoadingCount() {
        state.loadingCount++;
    },
    decreaseLoadingCount() {
        state.loadingCount--;
    },
};
