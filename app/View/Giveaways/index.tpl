{% extends 'Common/base.tpl' %}

{% set title = 'Giveaways' %}

{% block content %}

    <p>A giveaway is a set of items that we give away for free to players for a specific period of time. For example, we could give away free pumpkins to all TF2 players during the week of Halloween.</p>

    <p>Each giveaway is specific to a game/division and can optionally be restricted to only members. If you are eligible to claim any current giveaways, each one will be listed on the home page like a gift but with a 'Claim' button.</p>

    <p>If items are added to a giveaway after a player claims it, the remaining items will be claimable by the player again for the duration of the giveaway.</p>

    <p>Regarding game-specific giveaways, if a user connects with <code>!store</code> from a game server, that game will be used to look up related giveaways. If the user connects from somewhere else, the user's division will be used if applicable.</p>

    <p>
        {% if access.check('Giveaways', 'create') %}
            {{ html.link('+ Create a new Givewaway', {
                action: 'add'
            }, {
                class: 'giveaway_add'
            }) }}
        {% endif %}
    </p>

    <table class="giveaway_table">
        <tr>
            <th>Name</th>
            <th>Start</th>
            <th>Thru</th>
            <th>Restriction</th>
            <th>Status</th>
        </tr>
        {% for giveaway in giveaways %}
            <tr>
                <td class="giveaway_row_name">
                    {% if access.check('Giveaways', 'update') %}
                        {{ html.link(giveaway.name, {
                            action: 'edit',
                            id: giveaway.giveaway_id
                        }) }}
                    {% else %}
                        {{ giveaway.name }}
                    {% endif %}
                </td>
                <td>
                    <abbr title="{{ fn.formatTime(_context, giveaway.start_date) }}">
                        {{ giveaway.start_date ? time.format(giveaway.start_date, '%m-%d-%Y') : '' }}
                    </abbr>
                </td>
                <td>
                    <abbr title="{{ fn.formatTime(_context, giveaway.end_date) }}">
                        {{ giveaway.end_date ? time.format(giveaway.end_date, '%m-%d-%Y') : 'Never' }}
                    </abbr>
                </td>
                <td>
                    {% if giveaway.is_member_only %}
                        <span class="member-tag">rxg</span>
                    {% endif %}
                    {{ divisions[giveaway.game].abbr }}
                </td>
                <td>
                    {% if giveaway.status > 0 %}
                        <span class="giveaway_status_upcoming">Upcoming</span>
                    {% elseif giveaway.status < 0 %}
                        <span class="giveaway_status_expired">Expired</span>
                    {% else %}
                        <span class="giveaway_status_active">Active</span>
                    {% endif %}
                </td>
            </tr>
        {% endfor %}
    </table>

    {% include 'Common/activity.inc.tpl' with {
        'title': 'Recent Claims'
    } %}

{% endblock %}
