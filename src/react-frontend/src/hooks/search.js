const handleSearch = (e) => {
  resetUrlParams()
  const query = e.target.value
  setQuery(query)
  setPage(1)
  setLastPage(false)
}

const debouncedSearch = useMemo(() => lodash.debounce(handleSearch, 200), [])
