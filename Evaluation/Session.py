class Session(object):

	def __init__(self, specialPage, start, end, entityId, resultStart, resultEnd):
		super(Session, self).__init__()
		self.specialPage = specialPage
		self.start = start
		self.end = end
		self.entityId = entityId
		self.resultStart = resultStart
		self.resultEnd = resultEnd