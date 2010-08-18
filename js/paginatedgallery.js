function PaginatedGallery(elem, page, width)
{
	this.gallery = elem;

	this.scroller = $(this.gallery).find('.pagination_scroller');
	this.pagination_window = $(this.gallery).find('.pagination_window');
	this.curPage = page;
	this.pageWidth = width;
	this.maxPage = $(this.gallery).find('.pagination_page').length;

	this.MoveGallery = function(page) 
	{
		if(page > this.maxPage)
			page = 1;

		if(page < 1)
			page = this.maxPage;			

		var pos = this.pagination_window.position();
		pos = (this.pageWidth * page) - (this.pageWidth);
		this.scroller.animate({ left: '-' + pos + 'px' }, { duration: 500 } );
		this.curPage = page;

		gallery.find('.pagination_page').removeClass('selected');
		$(gallery.find('.pagination_page')[page-1]).addClass('selected');


	}

	this.NextPage = function()
	{
		var page = curPage + 1;
		this.MoveGallery(page);
	}

	this.PrevPage = function()
	{
		var page = curPage - 1;
		this.MoveGallery(page);
	}

	this.gallery.find('.pagination_page').click
	(
		function(i)
		{
			var page = $(this).attr("data");
			MoveGallery(page);
			return false;
		}
	);

	this.gallery.find('.pagination_next').click
	(
		function(i)
		{
			NextPage();
			return false;
		}
	);

	this.gallery.find('.pagination_prev').click
	(
		function(i)
		{
			PrevPage();
			return false;
		}
	);	
}