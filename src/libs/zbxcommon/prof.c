#include "zbxalgo.h"
#include "log.h"

typedef struct
{
	const char	*func_name;
	double		start;
	double		sec;
	unsigned int	locked;
}
ZBX_FUNC_PROFILE;

static zbx_vector_ptr_t	zbx_func_profiles;
static volatile int	zbx_prof_enabled;

static int	zbx_default_ptr_ptr_compare_func(const void *d1, const void *d2)
{
	const char	*p1 = *(const char **)d1;
	const char	*p2 = *(const char **)d2;

	return zbx_default_ptr_compare_func(p1, p2);
}

void	*zbx_prof_start(const char *func_name)
{
	if (1 == zbx_prof_enabled)
	{
		int			i;
		ZBX_FUNC_PROFILE	*func_profile;

		if (0 == zbx_func_profiles.values_alloc)
			zbx_vector_ptr_create(&zbx_func_profiles);

		if (FAIL == (i = zbx_vector_ptr_bsearch(&zbx_func_profiles, &func_name,
			zbx_default_ptr_ptr_compare_func)))
		{
			func_profile = zbx_malloc(NULL, sizeof(ZBX_FUNC_PROFILE));
			func_profile->func_name = func_name;
			func_profile->sec = 0;
			func_profile->locked = 0;

			zbx_vector_ptr_append(&zbx_func_profiles, func_profile);
			zbx_vector_ptr_sort(&zbx_func_profiles, zbx_default_ptr_ptr_compare_func);
		}
		else
			func_profile = zbx_func_profiles.values[i];

		func_profile->locked++;
		func_profile->start = zbx_time();

		return func_profile;
	}

	return NULL;
}

void	zbx_prof_end(void *func_profile)
{
	if (NULL != func_profile && 1 == zbx_prof_enabled)
	{
		((ZBX_FUNC_PROFILE *)func_profile)->sec += zbx_time() - ((ZBX_FUNC_PROFILE *)func_profile)->start;
		((ZBX_FUNC_PROFILE *)func_profile)->start = 0;
	}
}

void	zbx_print_prof(void)
{
	if (1 == zbx_prof_enabled)
	{
		int			i;
		ZBX_FUNC_PROFILE	*func_profile;
		static char		*sql = NULL;
		static size_t		sql_alloc;
		size_t			sql_offset = 0;

		for (i = 0; i < zbx_func_profiles.values_num; i++)
		{
			func_profile = zbx_func_profiles.values[i];
			zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, "%s() locked:%u seconds:"ZBX_FS_DBL "\n",
					func_profile->func_name, func_profile->locked, func_profile->sec);
		}

		if (0 != sql_offset)
			zabbix_log(LOG_LEVEL_INFORMATION, "Mutex contention: %s", sql);
	}
	else
		zbx_reset_prof();
}

void	zbx_reset_prof(void)
{
	if (0 != zbx_func_profiles.values_alloc)
	{
		zbx_vector_ptr_clear_ext(&zbx_func_profiles, zbx_ptr_free);
		zbx_vector_ptr_destroy(&zbx_func_profiles);
	}
}

void	zbx_enable_prof(void)
{
	zbx_prof_enabled = 1;
}

void	zbx_disable_prof(void)
{
	zbx_prof_enabled = 0;
}
