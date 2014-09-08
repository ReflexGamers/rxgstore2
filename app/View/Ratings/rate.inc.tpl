
<div class="rateit bigstars" id="item_rateit" data-rateit-value="{{ ratings.average / 2 }}"
	 data-rateit-starwidth="32" data-rateit-starheight="32"
	 data-rateit-readonly="{{ userCanRate ? '' : 'true' }}"
	 data-rateit-resetable="false"
	 data-href="{{ html.url({'controller': 'ratings', 'action': 'rate', 'id': item.item_id}) }}"></div>
<br>
{% if ratings.count > 0 %}
	<strong>{{ ratings.average/2|round(1) }}</strong>/5 (out of {{ ratings.count }} {{ ratings.count > 1 ? 'ratings' : 'rating' }})
{% else %}
	Not yet rated
{% endif %}
